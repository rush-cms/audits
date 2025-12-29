# Sprint 4: Performance and Resource Limits (HIGH PRIORITY)

**Estimated Duration:** 3-4 days
**Priority:** 🟠 HIGH
**Risk:** High - resource exhaustion in production

---

## Objective

Implement resource limits, optimize performance bottlenecks, and prevent resource exhaustion that could crash the service or rack up costs.

---

## Problem Context

### 1. Browsershot Memory Bomb

**Current Problem:**
```php
Browsershot::html($html)
    ->format('A4')
    ->margins(15, 15, 15, 15)
    ->save($path);
// No timeout, no memory limit, no process limit
```

**Attack Scenario:**
```
POST /api/v1/scan
{"url": "https://heavy-site-with-4k-images.com"}
```

**What Happens:**
- Chromium loads 100+ high-res images
- Uses 8GB+ RAM
- Takes 5+ minutes to render
- Kills other processes due to OOM
- Crashes server

**Production Impact:**
- One malicious/heavy site can take down entire service
- Multiple concurrent PDFs = server crash
- No protection against runaway processes

### 2. Screenshot Storage Never Cleaned

**Current Problem:**
- `PrunePdfsCommand` exists
- `PruneScreenshotsCommand` **does not exist**
- Screenshots accumulate forever in `storage/app/public/screenshots/`

**Math:**
```
1 audit = 2 screenshots × ~500KB = 1MB
1000 audits/day = 1GB/day
30 days = 30GB just in screenshots
```

**Plus:**
- Screenshots aren't even used after PDF generation
- Should be deleted immediately after PDF created

### 3. No Concurrent Job Limits

**Current Problem:**
Queue worker can spawn unlimited jobs simultaneously:

```
100 concurrent GenerateAuditPdfJobs
= 100 Chromium processes
= 100 × 500MB RAM = 50GB RAM
= Server dies
```

**Need:**
- Limit concurrent PDF generations to 3
- Limit concurrent screenshots to 5
- Queue other jobs until slots available

### 4. No Request Size Limits

**Current Problem:**
API doesn't validate request payload size. Attacker could:

```
POST /api/v1/scan
{"url": "a".repeat(10MB)}
```

Causes:
- PHP memory limit reached
- Request parsing takes forever
- DoS via large payloads

### 5. PageSpeed API Quota Management

**Current Problem:**
No tracking or limiting of PageSpeed API calls:

```
Without API key:
- 6 requests/minute
- No tracking = easy to hit limit
- Hitting limit = service breaks for everyone
```

---

## Tasks

### Task 4.1: Implement Browsershot Resource Limits

**Objective:** Prevent runaway Chromium processes from crashing the server.

**Implementation:**

1. **Add Timeout to Browsershot:**
   ```php
   Browsershot::html($html)
       ->timeout(60)  // Kill process after 60s
       ->format('A4')
       ->margins(15, 15, 15, 15)
       ->save($path);
   ```

2. **Add Memory Limit:**
   ```php
   ->setOption('args', [
       '--no-sandbox',
       '--disable-dev-shm-usage',
       '--disable-gpu',
       '--max-old-space-size=512',  // 512MB max per process
   ])
   ```

3. **Add Concurrent Process Limit:**
   - Create `BrowsershotLimiter` using Redis locks
   - Max 3 concurrent PDF generations
   - Max 5 concurrent screenshot captures
   - Queue others until slot available

4. **Configure via ENV:**
   ```env
   BROWSERSHOT_TIMEOUT=60
   BROWSERSHOT_MEMORY_LIMIT=512
   BROWSERSHOT_MAX_CONCURRENT_PDF=3
   BROWSERSHOT_MAX_CONCURRENT_SCREENSHOTS=5
   ```

5. **Monitor Browsershot Processes:**
   - Track active Chromium process count
   - Alert if > 10 processes
   - Auto-kill processes running > timeout

6. **Fallback on Timeout:**
   - If PDF generation times out
   - Retry once with longer timeout (120s)
   - If still fails, mark audit as failed

**Acceptance Criteria:**
- [ ] Browsershot has 60s timeout
- [ ] Memory limited to 512MB per process
- [ ] Max 3 concurrent PDF generations
- [ ] Max 5 concurrent screenshots
- [ ] Timeouts trigger retry once
- [ ] Monitoring tracks process count

---

### Task 4.2: Implement Screenshot Cleanup

**Objective:** Delete screenshots immediately after PDF generation to save disk space.

**Implementation:**

1. **Delete Screenshots After PDF:**
   In `GenerateAuditPdfJob`:
   ```php
   $pdfPath = $pdfGenerator->generate($auditData, $this->lang);

   // Delete screenshots immediately after PDF
   if ($auditData->desktopScreenshot) {
       Storage::delete($auditData->desktopScreenshot);
   }
   if ($auditData->mobileScreenshot) {
       Storage::delete($auditData->mobileScreenshot);
   }
   ```

2. **Create `PruneOrphanedScreenshotsCommand`:**
   - Find screenshots not associated with any audit
   - Find screenshots > 24 hours old
   - Delete them

3. **Schedule Daily:**
   ```php
   $schedule->command('audit:prune-orphaned-screenshots')
       ->daily()
       ->at('03:00');
   ```

4. **Add Metrics:**
   - Total screenshots deleted
   - Disk space freed
   - Orphaned screenshots found

5. **Configuration:**
   ```env
   AUDITS_DELETE_SCREENSHOTS_AFTER_PDF=true
   AUDITS_ORPHANED_SCREENSHOTS_RETENTION_HOURS=24
   ```

**Acceptance Criteria:**
- [ ] Screenshots deleted after successful PDF generation
- [ ] Prune command removes orphaned screenshots
- [ ] Command runs daily
- [ ] Metrics tracked
- [ ] Configurable via .env

---

### Task 4.3: Implement Queue Concurrency Limits

**Objective:** Prevent resource exhaustion from too many concurrent jobs.

**Implementation:**

1. **Use Laravel's Redis Throttle:**
   ```php
   class GenerateAuditPdfJob implements ShouldQueue
   {
       public function middleware(): array
       {
           return [
               (new ThrottlesExceptions(10, 5))->backoff(5),
               new WithoutOverlapping($this->auditId),
               (new RateLimited('pdf-generation'))
                   ->allow(3)
                   ->every(1)
                   ->releaseAfter(60),
           ];
       }
   }
   ```

2. **Create Rate Limiters:**
   In `AppServiceProvider`:
   ```php
   RateLimiter::for('pdf-generation', function () {
       return Limit::perMinute(3);
   });

   RateLimiter::for('screenshot-capture', function () {
       return Limit::perMinute(5);
   });
   ```

3. **Prevent Job Overlap:**
   - Same audit shouldn't be processed twice
   - Use `WithoutOverlapping` middleware
   - Based on audit_id

4. **Monitor Queue Depth:**
   - Health check includes queue depth
   - Alert if queue depth > 100
   - Alert if jobs waiting > 5 minutes

5. **Configuration:**
   ```env
   QUEUE_PDF_CONCURRENCY=3
   QUEUE_SCREENSHOT_CONCURRENCY=5
   QUEUE_MAX_DEPTH_ALERT=100
   ```

**Acceptance Criteria:**
- [ ] Max 3 PDFs generated concurrently
- [ ] Max 5 screenshots captured concurrently
- [ ] Jobs queue when limit reached
- [ ] Same audit can't be processed twice
- [ ] Queue depth monitored

---

### Task 4.4: Add Request Size Validation

**Objective:** Prevent DoS via large payloads.

**Implementation:**

1. **Nginx/Apache Level:**
   ```nginx
   client_max_body_size 1M;
   ```

2. **Laravel Middleware:**
   Create `ValidateRequestSize` middleware:
   ```php
   - Check Content-Length header
   - If > 1MB, reject with 413 Payload Too Large
   ```

3. **Validate Individual Fields:**
   ```php
   'url' => ['required', 'string', 'max:2048'],
   'lang' => ['required', 'string', 'max:10'],
   'strategy' => ['required', 'string', 'max:10'],
   ```

4. **Response:**
   ```json
   {
     "error": "Payload too large",
     "max_size": "1MB",
     "received_size": "5MB"
   }
   ```

5. **Configuration:**
   ```env
   API_MAX_REQUEST_SIZE=1048576  # 1MB in bytes
   ```

**Acceptance Criteria:**
- [ ] Requests > 1MB rejected
- [ ] Returns 413 status code
- [ ] Clear error message
- [ ] Configurable max size

---

### Task 4.5: Implement PageSpeed API Quota Tracking

**Objective:** Prevent hitting PageSpeed API limits.

**Implementation:**

1. **Track API Calls:**
   ```php
   Cache::increment('pagespeed:calls:minute');
   Cache::increment('pagespeed:calls:hour');
   Cache::increment('pagespeed:calls:day');
   ```

2. **Check Before Call:**
   ```php
   $callsThisMinute = Cache::get('pagespeed:calls:minute', 0);

   if ($callsThisMinute >= 6) {  // Free tier limit
       // Wait or queue for later
       $this->release(60);  // Retry after 1 minute
       return;
   }
   ```

3. **Configuration:**
   ```env
   PAGESPEED_RATE_LIMIT_PER_MINUTE=6   # No API key
   PAGESPEED_RATE_LIMIT_PER_DAY=25000  # With API key
   ```

4. **Respect API Key Quotas:**
   - Without key: 6/minute
   - With key: 400/day (standard)
   - With elevated: 25,000/day

5. **Alert on Quota:**
   - If 80% of daily quota used → alert
   - If quota exceeded → pause audits for 24h

6. **Fallback:**
   - If quota exceeded, return cached data (if available)
   - Or queue audit for next day

**Acceptance Criteria:**
- [ ] API calls tracked in cache
- [ ] Limits enforced before calling API
- [ ] Job queued if limit reached
- [ ] Alerts at 80% quota
- [ ] Respects different quota tiers

---

### Task 4.6: Add Database Query Optimization

**Objective:** Prevent slow queries from degrading performance.

**Implementation:**

1. **Add Missing Indexes:**
   ```php
   $table->index(['status', 'created_at']);
   $table->index(['url', 'strategy', 'status']);
   ```

2. **Optimize Audit Queries:**
   - Use `select()` to fetch only needed columns
   - Eager load relationships if added
   - Add pagination to list endpoints

3. **Database Query Logging (dev only):**
   ```php
   if (app()->environment('local')) {
       DB::listen(function ($query) {
           if ($query->time > 100) {  // > 100ms
               Log::warning('Slow query', [
                   'sql' => $query->sql,
                   'time' => $query->time,
               ]);
           }
       });
   }
   ```

4. **Add `EXPLAIN` Command:**
   ```bash
   php artisan audit:explain-queries
   ```

   Runs common queries with EXPLAIN to detect missing indexes

5. **Monitor Query Performance:**
   - Health check includes average query time
   - Alert if queries > 500ms

**Acceptance Criteria:**
- [ ] Indexes added for common queries
- [ ] Slow query logging in dev
- [ ] Query optimization documented
- [ ] Health check monitors query performance

---

## Risks and Dependencies

**Risks:**
- **Too strict limits may impact UX:** Start permissive, tighten based on metrics
- **Screenshot deletion may fail:** Add error handling and orphan cleanup
- **Queue depth alerts may be noisy:** Tune thresholds based on production load

**Dependencies:**
- Redis for rate limiting (already dependency)
- No new external dependencies

---

## Required Tests

### Resource Limit Tests:
- [ ] Browsershot respects timeout
- [ ] Browsershot respects memory limit
- [ ] Concurrent job limits enforced
- [ ] Request size validation works

### Cleanup Tests:
- [ ] Screenshots deleted after PDF
- [ ] Orphan screenshot cleanup works
- [ ] Prune command doesn't delete active files

### API Quota Tests:
- [ ] PageSpeed calls tracked
- [ ] Limits enforced
- [ ] Jobs queued when limit reached

### Performance Tests:
- [ ] Common queries use indexes
- [ ] No N+1 queries
- [ ] Query times acceptable

---

## Definition of Done

- [ ] All tasks implemented
- [ ] Resource limits configured
- [ ] Cleanup commands working
- [ ] Tests passing
- [ ] Performance benchmarks met
- [ ] Documentation updated

---

## Documentation to Update

1. **docs/performance.md (CREATE):**
   - Resource limits explained
   - How to tune concurrency
   - PageSpeed API quota management
   - Performance optimization tips

2. **docs/configuration.md:**
   - Document all new ENV variables
   - Recommended values for different scales

3. **README.md:**
   - Mention resource limits
   - Link to performance guide

---

## Effort Estimation

| Task | Estimate | Priority |
|------|----------|----------|
| 4.1 Browsershot Limits | 4-6h | P0 |
| 4.2 Screenshot Cleanup | 3-4h | P0 |
| 4.3 Queue Concurrency | 3-4h | P0 |
| 4.4 Request Size Validation | 2-3h | P1 |
| 4.5 PageSpeed Quota Tracking | 4-5h | P0 |
| 4.6 Query Optimization | 3-4h | P1 |
| Testing | 4-6h | P0 |
| Documentation | 2-3h | P1 |

**Total:** 25-35 hours (3-5 days)
