# Sprint 3: Observability and Logging (CRITICAL)

**Estimated Duration:** 3-4 days
**Priority:** 🔴 CRITICAL
**Risk:** Medium - impossible to debug production issues

---

## Objective

Implement comprehensive logging, structured error tracking, and health checks to make the system observable and debuggable in production.

---

## Problem Context

### 1. Zero Logging Throughout Codebase

**Current Problem:**
No `Log::` calls anywhere in the codebase. In production:
- Can't trace audit lifecycle
- Can't debug why webhook failed
- Can't see why job failed
- Can't audit API usage
- Can't detect performance degradation

**Example Production Scenario:**
```
Client: "My webhook isn't being called!"

You: *checks logs*
Logs: *crickets*

You: *checks database*
Database: audit_id exists, status=completed, pdf_path set

You: "...I have no idea what happened"
```

### 2. Error Messages Are Useless

**Current Problem:**
```php
'Failed to fetch PageSpeed data'
'Failed to capture screenshots'
'Failed to generate PDF'
```

**What's missing:**
- Which URL?
- Which audit_id?
- What was the actual error?
- What was the API response?
- How long did it take before failing?

**Production Impact:**
Client: "Audit abc-123 failed"
You: *searches database*
Error: "Failed to fetch PageSpeed data"
You: "...that tells me nothing"

### 3. No Health Checks

**Current Problem:**
- No `/health` endpoint
- No monitoring of queue depth
- No monitoring of failed jobs
- No monitoring of disk space
- No monitoring of Chromium process count

**What Happens:**
```
00:00 - Queue worker crashes
01:00 - 1000 jobs piling up
02:00 - Disk 95% full (PDFs not being pruned)
03:00 - Client calls: "Nothing works!"
03:00 - You: *wakes up, checks... what exactly?*
```

### 4. Webhook Failures Are Silent

**Current Problem:**
```php
Http::timeout(30)->post($webhookUrl, $payload->toArray());
// No logging, no error checking, no retry visibility
```

**Scenarios:**
- Webhook URL returns 500 → silent
- Webhook URL times out → silent
- Webhook URL returns 200 but wrong data → silent
- Network error → silent (job retries, but you don't know)

---

## Tasks

### Task 3.1: Implement Structured Logging

**Objective:** Add contextual, searchable logging throughout the system.

**Implementation:**

1. **Create Log Contexts:**
   ```php
   // Every log should include:
   [
     'audit_id' => $audit->id,
     'url' => $audit->url,
     'strategy' => $audit->strategy,
     'user_id' => $user->id ?? null,
     'ip' => request()->ip(),
   ]
   ```

2. **Log Audit Lifecycle:**

   **AuditController:**
   - Request received (with params)
   - Idempotency check result
   - Audit created/returned
   - Job dispatched

   **FetchPageSpeedJob:**
   - Job started
   - API call made (URL, strategy)
   - API response received (status, duration)
   - Data parsed successfully
   - Next job dispatched
   - Job failed (with error details)

   **TakeScreenshotsJob:**
   - Job started
   - Screenshot attempts (desktop/mobile)
   - Screenshot success/failure (with sizes, duration)
   - Next job dispatched
   - Job failed (with error details)

   **GenerateAuditPdfJob:**
   - Job started
   - PDF generation started
   - PDF generated (size, duration)
   - Webhook dispatched
   - Job completed
   - Job failed (with error details)

3. **Log Levels:**
   - **DEBUG:** Detailed flow (dev only)
   - **INFO:** Lifecycle events, successful operations
   - **WARNING:** Degraded operations (PDF without screenshots)
   - **ERROR:** Failures that will be retried
   - **CRITICAL:** Failures that won't be retried, data loss

4. **Performance Logging:**
   - Log duration of each operation
   - Log queue wait time
   - Log API response times
   - Use Log::info() with duration context

5. **Use Laravel Log Channels:**
   ```php
   // config/logging.php
   'channels' => [
       'audits' => [
           'driver' => 'daily',
           'path' => storage_path('logs/audits.log'),
           'level' => 'info',
           'days' => 14,
       ],
       'webhooks' => [
           'driver' => 'daily',
           'path' => storage_path('logs/webhooks.log'),
           'level' => 'info',
           'days' => 7,
       ],
   ]
   ```

**Acceptance Criteria:**
- [ ] Every job logs start/end with context
- [ ] Every external API call is logged (request + response)
- [ ] Every error includes full context
- [ ] Logs are searchable by audit_id
- [ ] Performance metrics are logged
- [ ] Separate log channels for audits/webhooks

---

### Task 3.2: Implement Comprehensive Error Tracking

**Objective:** Make errors actionable and debuggable.

**Implementation:**

1. **Detailed Error Messages:**

   Instead of:
   ```php
   throw new Exception('Failed to fetch PageSpeed data');
   ```

   Use:
   ```php
   throw new PageSpeedFetchException(
       "Failed to fetch PageSpeed data for {$url}: {$apiError}",
       context: [
           'url' => $url,
           'strategy' => $strategy,
           'api_status' => $response->status(),
           'api_body' => $response->body(),
           'duration_ms' => $durationMs,
       ]
   );
   ```

2. **Create Custom Exceptions:**
   - `PageSpeedFetchException`
   - `ScreenshotCaptureException`
   - `PdfGenerationException`
   - `WebhookDeliveryException`
   - `SafeUrlException`

3. **Exception Context:**
   All exceptions should carry:
   - Audit ID
   - URL being processed
   - Current step in pipeline
   - Relevant API responses
   - System state (memory, disk, queue depth)

4. **Error Handler:**
   - Custom exception handler
   - Log to appropriate channel based on exception type
   - Include stack trace for debugging
   - Sanitize sensitive data (tokens, secrets)

5. **Store Error Details in Audit:**
   ```php
   $audit->update([
       'error_message' => $exception->getMessage(),
       'error_context' => [
           'exception' => get_class($exception),
           'file' => $exception->getFile(),
           'line' => $exception->getLine(),
           'trace' => $exception->getTraceAsString(),
       ],
   ]);
   ```

**Acceptance Criteria:**
- [ ] Custom exceptions for each failure type
- [ ] Error messages include full context
- [ ] Errors stored in audit record
- [ ] Stack traces logged (but not in webhook)
- [ ] Sensitive data sanitized in logs

---

### Task 3.3: Add Health Check Endpoint

**Objective:** Allow monitoring systems to detect issues automatically.

**Implementation:**

1. **Create `/health` Endpoint:**
   ```
   GET /health
   ```

   Returns:
   ```json
   {
     "status": "healthy",
     "checks": {
       "database": "ok",
       "redis": "ok",
       "queue": "ok",
       "disk": "ok",
       "chromium": "ok"
     },
     "metrics": {
       "queue_depth": 23,
       "failed_jobs_last_hour": 2,
       "disk_usage_percent": 45,
       "average_processing_time_seconds": 45
     }
   }
   ```

2. **Health Checks:**

   **Database Check:**
   - Can connect?
   - Can execute simple query?
   - Response time < 100ms?

   **Redis Check:**
   - Can connect?
   - Can set/get key?
   - Response time < 50ms?

   **Queue Check:**
   - Queue depth < threshold (100 jobs)
   - Failed jobs < threshold (10 in last hour)
   - Oldest job age < threshold (5 minutes)

   **Disk Check:**
   - Disk usage < 90%
   - PDF storage < 80% of quota
   - Screenshot storage < 80% of quota

   **Chromium Check:**
   - Chromium binary exists?
   - Can spawn process?
   - Active processes < threshold (5)

3. **Health Status Codes:**
   - 200: All checks passed
   - 503: One or more checks failed
   - 500: Health check itself crashed

4. **Detailed Health Endpoint:**
   ```
   GET /health/detailed
   ```

   Returns additional metrics:
   - Audits created (last hour, day, week)
   - Average processing time
   - Success rate
   - Most common errors
   - Current worker status

5. **Make Unauthenticated:**
   - Health checks shouldn't require token
   - Use IP whitelist if needed
   - Or create dedicated health check token

**Acceptance Criteria:**
- [ ] `/health` endpoint returns 200 when healthy
- [ ] Returns 503 when any check fails
- [ ] Checks complete in < 2 seconds
- [ ] Checks don't impact production traffic
- [ ] Monitoring can scrape endpoint

---

### Task 3.4: Implement Webhook Observability

**Objective:** Know when webhooks fail and why.

**Implementation:**

1. **Log Webhook Attempts:**
   ```php
   Log::channel('webhooks')->info('Dispatching webhook', [
       'audit_id' => $audit->id,
       'webhook_url' => $webhookUrl,
       'payload' => $payload,
   ]);
   ```

2. **Log Webhook Responses:**
   ```php
   Log::channel('webhooks')->info('Webhook delivered', [
       'audit_id' => $audit->id,
       'status' => $response->status(),
       'duration_ms' => $durationMs,
       'response_body' => $response->body(),
   ]);
   ```

3. **Log Webhook Failures:**
   ```php
   Log::channel('webhooks')->error('Webhook failed', [
       'audit_id' => $audit->id,
       'error' => $exception->getMessage(),
       'attempt' => $this->attempts(),
       'will_retry' => $this->attempts() < $this->tries,
   ]);
   ```

4. **Add Webhook Status to Audit:**
   ```php
   $audit->update([
       'webhook_delivered_at' => now(),
       'webhook_status' => $response->status(),
       'webhook_attempts' => $this->attempts(),
   ]);
   ```

5. **Webhook Metrics:**
   - Success rate (200 responses / total attempts)
   - Average response time
   - Most common failure codes
   - Retry frequency

6. **Alert on Webhook Issues:**
   - If webhook fails > 5 times in 1 hour → alert
   - If webhook timeout > 10s consistently → alert
   - If webhook returns 5xx → alert (possible client issue)

**Acceptance Criteria:**
- [ ] All webhook attempts logged
- [ ] Response status and body logged
- [ ] Failures include error details
- [ ] Audit records webhook delivery status
- [ ] Metrics tracked for monitoring
- [ ] Alerts configured for failures

---

### Task 3.5: Create Audit Trail

**Objective:** Track who did what and when.

**Implementation:**

1. **Add Audit Metadata:**
   ```php
   audits table:
   - created_by_token_id
   - created_by_ip
   - user_agent
   ```

2. **Log API Requests:**
   ```php
   Log::channel('audits')->info('API request', [
       'endpoint' => '/api/v1/scan',
       'token_id' => $token->id,
       'token_name' => $token->name,
       'ip' => request()->ip(),
       'user_agent' => request()->userAgent(),
       'payload' => $request->validated(),
   ]);
   ```

3. **Track State Changes:**
   - When status changes → log old and new status
   - When PDF generated → log file size, duration
   - When audit failed → log reason and step

4. **Create Activity Log Table (optional):**
   ```
   audit_activities:
   - audit_id
   - activity_type (created, processing, completed, failed)
   - metadata (JSON)
   - created_at
   ```

   Allows full history reconstruction

5. **GDPR Compliance:**
   - IP addresses should be hashable/anonymizable
   - Configurable retention period
   - Ability to export audit trail for specific user

**Acceptance Criteria:**
- [ ] Token and IP recorded on audit creation
- [ ] All API requests logged
- [ ] State changes tracked
- [ ] Activity log queryable
- [ ] GDPR compliant

---

## Risks and Dependencies

**Risks:**
- **Logs grow quickly:** Implement log rotation (daily files, 14 day retention)
- **Logging adds latency:** Use async logging (queue)
- **Sensitive data in logs:** Sanitize tokens, secrets, personal data

**Dependencies:**
- No new dependencies (Laravel logging built-in)
- Disk space for logs (plan for 1-2GB)

---

## Required Tests

### Logging Tests:
- [ ] Jobs log start/end events
- [ ] Errors include full context
- [ ] Webhook attempts are logged
- [ ] API requests are logged

### Health Check Tests:
- [ ] Health endpoint returns 200 when healthy
- [ ] Returns 503 when check fails
- [ ] Detailed endpoint includes metrics
- [ ] Checks complete in < 2s

### Audit Trail Tests:
- [ ] Audit includes creator metadata
- [ ] State changes are tracked
- [ ] Activity log is queryable

---

## Definition of Done

- [ ] All tasks implemented
- [ ] Logging present in all jobs
- [ ] Health checks functional
- [ ] Webhook logging complete
- [ ] Tests passing
- [ ] Log rotation configured
- [ ] Documentation updated

---

## Documentation to Update

1. **docs/troubleshooting.md (CREATE):**
   - How to read logs
   - Common error patterns
   - How to use health checks
   - How to debug failed audits

2. **docs/monitoring.md (CREATE):**
   - Health check endpoints
   - Metrics available
   - How to set up alerts
   - Log file locations

3. **README.md:**
   - Link to troubleshooting guide
   - Mention observability features

---

## Effort Estimation

| Task | Estimate | Priority |
|------|----------|----------|
| 3.1 Structured Logging | 6-8h | P0 |
| 3.2 Error Tracking | 4-6h | P0 |
| 3.3 Health Checks | 4-5h | P0 |
| 3.4 Webhook Observability | 3-4h | P0 |
| 3.5 Audit Trail | 3-4h | P1 |
| Testing | 4-6h | P0 |
| Documentation | 3-4h | P1 |

**Total:** 27-37 hours (3-5 days)
