# Sprint 2: Reliability and Race Conditions (CRITICAL)

**Estimated Duration:** 4-6 days
**Priority:** 🔴 CRITICAL
**Risk:** High - data corruption in production

---

## Objective

Eliminate race conditions in audit creation, implement state-based idempotency (not time-based), and ensure the system is resilient to partial failures in the job pipeline.

---

## Problem Context

### 1. Race Condition in CreateOrFindAuditAction

**Current Problem:**
When two simultaneous requests arrive for the same URL+strategy, both try to create the audit:

```
Request A                          Request B
│                                  │
├─ generateIdempotencyKey()        ├─ generateIdempotencyKey()
├─ firstOrCreate() → creates       ├─ firstOrCreate() → ERROR!
└─ returns audit                   └─ QueryException: Duplicate entry
```

**Why it happens:**
`firstOrCreate()` is not atomic. There's a gap between "check if exists" and "create" where another process can insert the same record.

**Production Frequency:**
- Low load: rare
- Medium load (10+ req/s): common
- Simultaneous webhook retries: very common

**Impact:**
- 500 Internal Server Error for client
- Lost request (not automatically retried)
- Poor UX
- Logs full of exceptions

### 2. Time-Based Idempotency is Problematic

**Current Problem:**
```
Idempotency Key = hash(url + strategy + time_window_start)
Window: 60 minutes
```

**Problematic Scenarios:**

**Scenario A: Audit Failed**
```
10:00 - Audit created, fails on PageSpeed fetch
10:05 - Client retry → same audit_id returned
        But audit is "failed", no processing happens
10:55 - Client still waiting...
11:01 - Window expires, new audit created
        But client lost reference to original audit_id
```

**Scenario B: Audit Completed**
```
10:00 - Audit created and completed successfully
10:30 - Site was updated, client wants new audit
        Request returns same audit_id (cached)
        PDF is from old site!
11:01 - Finally can create new audit
```

**Scenario C: Clock Skew**
```
Server A (clock +2 min): window = 10:00-11:00
Server B (clock correct): window = 9:58-10:58

Same request on different servers = different idempotency keys = duplicate audits
```

### 3. Job Pipeline Without Compensation

**Current Problem:**
```
FetchPageSpeedJob (90s timeout, 2 tries)
  ↓ success
TakeScreenshotsJob (120s timeout, 1 try)
  ↓ FAILS (site blocked screenshots)
GenerateAuditPdfJob → never executed
```

**Result:**
- Audit marked as "failed"
- PageSpeed data was fetched (API quota spent)
- PageSpeed data is discarded (not saved)
- Client needs to re-submit
- PageSpeed API called again (quota wasted)

**Issues:**
1. **No partial data saved:** PageSpeed data should be saved even if screenshot fails
2. **No intelligent retry:** Screenshot fail could allow PDF without screenshots
3. **No dead letter queue:** Failed jobs are lost
4. **No graceful degradation:** Screenshot fail = audit fail (should be warning)

---

## Tasks

### Task 2.1: Fix Race Condition with Optimistic Locking

**Objective:** Ensure simultaneous requests don't cause exceptions.

**Implementation:**

1. **Strategy: Optimistic Locking + Retry**

   When `firstOrCreate()` fails with duplicate key:
   - Catch exception
   - Fetch existing audit
   - Return normally

   Advantage: Simple, no heavy locks

2. **Add Retry Logic:**
   - Max 3 attempts
   - Exponential backoff (10ms, 20ms, 40ms)
   - Log when retry happens (to monitor frequency)

3. **Monitor Race Conditions:**
   - Metric counter: `audits.race_condition.retries`
   - If counter high = architectural problem

4. **Alternative: Pessimistic Locking (if retry doesn't solve):**
   ```
   Use DB::transaction with lockForUpdate if needed
   ```

   Disadvantage: Slower, but guarantees atomicity

**Acceptance Criteria:**
- [ ] 2 simultaneous requests for same URL don't cause exception
- [ ] Both return same `audit_id`
- [ ] Only 1 audit created in database
- [ ] Retry logic works in 99.9% of cases
- [ ] Metrics record when retry happens

---

### Task 2.2: State-Based Idempotency

**Objective:** Idempotency should consider audit state, not just time.

**New Logic:**

1. **Fetch Existing Audit:**
   ```
   Search by (url + strategy)
   - If doesn't exist → create new
   - If exists:
     - Status "pending" or "processing" → return existing
     - Status "completed" → create new (allow re-scan)
     - Status "failed" → check age
       - < 5 minutes → return existing (retry in progress)
       - >= 5 minutes → create new (allow retry)
   ```

2. **Remove Time Window:**
   - `idempotency_key` continues to exist for unique constraint
   - But logic no longer depends on time window
   - Avoids clock skew problems

3. **Configure "Failed Audit TTL":**
   ```env
   AUDITS_FAILED_RETRY_AFTER=300  # 5 minutes
   ```

   Allows automatic retry after X seconds without creating duplicate audit

4. **Add `last_attempt_at` to Audit:**
   - Timestamp of last processing attempt
   - Used to determine if should retry or return existing

**Acceptance Criteria:**
- [ ] "completed" audit allows new scan immediately
- [ ] "failed" audit < 5min returns existing
- [ ] "failed" audit >= 5min creates new
- [ ] "pending" audit always returns existing
- [ ] "processing" audit always returns existing
- [ ] Clock skew doesn't affect behavior

---

### Task 2.3: Save Partial Data on Failure

**Objective:** Don't waste already-fetched data when part of pipeline fails.

**Implementation:**

1. **Add Columns to Audit:**
   ```
   - pagespeed_data (JSON, nullable)
   - screenshots_data (JSON, nullable)
   - processing_steps (JSON, nullable)
   ```

2. **FetchPageSpeedJob - Save PageSpeed Data:**
   - After successful fetch
   - Before dispatching TakeScreenshotsJob
   - Save in `audit.pagespeed_data`
   - If TakeScreenshotsJob fails, data is preserved

3. **TakeScreenshotsJob - Save Screenshots:**
   - After successful capture
   - Before dispatching GenerateAuditPdfJob
   - Save in `audit.screenshots_data`
   - If GenerateAuditPdfJob fails, screenshots are preserved

4. **Step Tracking:**
   ```json
   {
     "steps": [
       {
         "name": "fetch_pagespeed",
         "status": "completed",
         "completed_at": "2025-12-29T10:00:00Z"
       },
       {
         "name": "take_screenshots",
         "status": "failed",
         "error": "Timeout after 120s",
         "failed_at": "2025-12-29T10:02:00Z"
       }
     ]
   }
   ```

5. **Intelligent Retry:**
   - If PageSpeed data exists, don't re-fetch
   - If screenshots exist, don't re-capture
   - Skip to failed step

**Acceptance Criteria:**
- [ ] PageSpeed data saved before next job
- [ ] Screenshots saved before next job
- [ ] `processing_steps` records progress
- [ ] Retry skips already completed steps
- [ ] Partial data shown in GET /audits/{id}

---

### Task 2.4: Graceful Degradation - PDF Without Screenshots

**Objective:** Screenshot failure shouldn't prevent PDF generation.

**Implementation:**

1. **TakeScreenshotsJob - Fail Gracefully:**
   - If BOTH screenshots fail → mark `screenshots_failed: true`
   - If at least 1 succeeds → continue normally
   - ALWAYS dispatch GenerateAuditPdfJob

2. **GenerateAuditPdfJob - Placeholder Screenshots:**
   - Check if screenshots exist
   - If not: use placeholder image
   - Add watermark "Screenshots unavailable"
   - Continue generation normally

3. **Add Flag in Webhook:**
   ```json
   {
     "screenshots_included": false,
     "screenshot_error": "Timeout during capture"
   }
   ```

4. **Configure Behavior:**
   ```env
   AUDITS_REQUIRE_SCREENSHOTS=false  # true = fail audit if screenshot fails
   ```

**Acceptance Criteria:**
- [ ] Screenshot fail doesn't prevent PDF
- [ ] PDF generated with placeholder when screenshots missing
- [ ] Webhook indicates if screenshots included
- [ ] Behavior configurable via .env
- [ ] Audit status = "completed" even without screenshots

---

### Task 2.5: Implement Dead Letter Queue

**Objective:** Jobs that failed definitively shouldn't be lost.

**Implementation:**

1. **Configure Failed Jobs Table:**
   - Laravel has native support
   - `php artisan queue:failed-table`
   - Migration already exists, activate

2. **Failed Job Handler:**
   - Create `FailedJobHandler`
   - Log structured data when job fails definitively
   - Notify (Slack/email) on critical failures
   - Allow manual retry via artisan command

3. **Retry Command:**
   ```bash
   php artisan queue:retry {id}
   php artisan queue:retry --queue=audits
   ```

4. **Failed Jobs Monitor:**
   - Health check that alerts if failed jobs > threshold
   - Dashboard showing recent failed jobs
   - Metrics: `audits.jobs.failed.total`

5. **Auto-cleanup:**
   - Failed jobs > 30 days are deleted
   - Configurable via `AUDITS_FAILED_JOBS_RETENTION_DAYS`

**Acceptance Criteria:**
- [ ] Failed jobs saved in `failed_jobs` table
- [ ] Handler structures logs correctly
- [ ] Retry command works
- [ ] Health check detects failed jobs accumulation
- [ ] Auto-cleanup removes old failed jobs

---

### Task 2.6: Exponential Backoff for Retries

**Objective:** Failed jobs should be retried with increasing delay.

**Implementation:**

1. **Configure Backoff Strategy:**
   ```
   FetchPageSpeedJob:
     - Attempt 1: immediate
     - Attempt 2: 30s delay

   GenerateAuditPdfJob:
     - Attempt 1: immediate
     - Attempt 2: 60s delay
   ```

2. **Release Strategy:**
   - Use `$this->release($seconds)` instead of exception
   - Allows fine control of delay

3. **Configurable Max Attempts:**
   ```env
   AUDITS_JOB_MAX_ATTEMPTS=3
   AUDITS_JOB_BACKOFF_BASE=30  # seconds
   ```

4. **Jitter to Prevent Thundering Herd:**
   - Add random jitter (±20%)
   - Prevents all jobs retrying at same moment

**Acceptance Criteria:**
- [ ] Jobs use exponential backoff
- [ ] Delay increases each attempt
- [ ] Jitter prevents thundering herd
- [ ] Config allows adjusting strategy
- [ ] Logs show attempt number and next delay

---

## Risks and Dependencies

**Risks:**
- **Adding JSON columns may increase DB size:** Monitor growth, add indexes if needed
- **Graceful degradation may confuse clients:** Document behavior clearly
- **Retry logic increases complexity:** Add detailed logs for debugging

**Dependencies:**
- Migration for new columns (backward compatible)
- Failed jobs table (already in Laravel, just activate)
- No new external dependencies

**Migration:**
- Existing audits work normally (nullable columns)
- Backward compatible behavior (screenshots still required by default)
- New idempotency logic is more permissive (less breaking)

---

## Required Tests

### Concurrency Tests:
- [ ] 10 simultaneous requests for same URL
- [ ] Only 1 audit created
- [ ] All return same audit_id
- [ ] Zero exceptions

### Idempotency Tests:
- [ ] "completed" audit allows re-scan
- [ ] Recent "failed" audit returns existing
- [ ] Old "failed" audit creates new
- [ ] "pending" audit returns existing

### Graceful Degradation Tests:
- [ ] Screenshot fail generates PDF with placeholder
- [ ] At least 1 screenshot = normal PDF
- [ ] Webhook indicates screenshots_included correctly

### Retry Tests:
- [ ] Job with retry uses correct backoff
- [ ] Failed jobs go to DLQ
- [ ] Manual retry works

### Partial Data Tests:
- [ ] PageSpeed data saved on failure
- [ ] Retry skips already completed steps
- [ ] GET /audits/{id} shows partial data

---

## Definition of Done

- [ ] All tasks implemented
- [ ] Concurrency tests passing (100 concurrent requests)
- [ ] Unit tests passing
- [ ] PHPStan level 8 no errors
- [ ] Migration created and tested
- [ ] Documentation updated
- [ ] Load testing performed (simulate race conditions)
- [ ] Code review approved

---

## Documentation to Update

1. **docs/api.md:**
   - Explain state-based idempotency
   - Document when re-scan is allowed
   - Explain `screenshots_included` in webhook

2. **docs/troubleshooting.md (CREATE):**
   - How to debug race conditions
   - How to retry failed jobs
   - How to monitor DLQ

3. **CHANGELOG.md:**
   - Breaking change: idempotency behavior changed
   - New feature: graceful degradation
   - New feature: partial data persistence

---

## Effort Estimation

| Task | Estimate | Priority |
|------|----------|----------|
| 2.1 Fix Race Condition | 4-6h | P0 |
| 2.2 State-Based Idempotency | 6-8h | P0 |
| 2.3 Partial Data | 8-10h | P0 |
| 2.4 Graceful Degradation | 4-6h | P1 |
| 2.5 Dead Letter Queue | 3-4h | P1 |
| 2.6 Exponential Backoff | 2-3h | P1 |
| Migration | 2h | P0 |
| Testing | 8-12h | P0 |
| Documentation | 3-4h | P1 |

**Total:** 40-55 hours (5-7 days, achievable in 4-6 with focus)
