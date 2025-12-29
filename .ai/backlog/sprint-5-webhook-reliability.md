# Sprint 5: Webhook Reliability (HIGH PRIORITY)

**Estimated Duration:** 2-3 days
**Priority:** 🟠 HIGH
**Risk:** Medium - webhook failures impact user experience

---

## Objective

Make webhook delivery reliable, observable, and debuggable. Ensure clients receive notifications even when their endpoints are temporarily unavailable.

---

## Problem Context

### 1. No Response Validation

**Current Problem:**
```php
Http::timeout(30)->post($webhookUrl, $payload->toArray());
// Fire and forget - no validation
```

**Issues:**
- Doesn't check HTTP status code
- Doesn't validate response body
- Client could return 500 and we'd never know
- Client could return 200 but fail to process

### 2. 30 Second Timeout is Too Long

**Current Problem:**
If client webhook endpoint is slow:
- Worker blocked for 30 seconds
- Queue throughput drops dramatically
- Other jobs delayed

**Math:**
```
3 workers × 30s timeout = can only handle 360 webhooks/hour
vs
3 workers × 5s timeout = 2,160 webhooks/hour
```

### 3. No Retry Strategy Documentation

**Current Problem:**
Jobs retry, but:
- Client doesn't know how many retries
- Client doesn't know retry schedule
- No documentation of retry behavior
- No way to manually trigger retry

### 4. No Webhook History

**Current Problem:**
Can't answer questions like:
- Was webhook delivered?
- How many attempts?
- What was the response?
- When was it delivered?

Essential for debugging client issues.

### 5. No Alternative Notification Methods

**Current Problem:**
If webhook fails completely:
- No email notification
- No Slack notification
- No admin dashboard alert
- Audit completed but client never knows

---

## Tasks

### Task 5.1: Implement Response Validation

**Objective:** Validate webhook responses and handle errors appropriately.

**Implementation:**

1. **Check Status Code:**
   ```php
   $response = Http::timeout(5)->post($webhookUrl, $payload);

   if ($response->successful()) {
       // 2xx response = success
       Log::channel('webhooks')->info('Webhook delivered successfully');
       return;
   }

   if ($response->clientError()) {
       // 4xx = client error, don't retry
       Log::channel('webhooks')->error('Webhook rejected by client');
       $this->fail();
       return;
   }

   if ($response->serverError()) {
       // 5xx = server error, retry
       Log::channel('webhooks')->warning('Webhook server error, retrying');
       throw new WebhookDeliveryException();
   }
   ```

2. **Validate Response Body (Optional):**
   ```php
   // If client wants acknowledgment
   $ack = $response->json('acknowledged', false);

   if (!$ack) {
       Log::warning('Webhook not acknowledged by client');
   }
   ```

3. **Handle Timeouts:**
   ```php
   try {
       $response = Http::timeout(5)->post($webhookUrl, $payload);
   } catch (ConnectionException $e) {
       Log::error('Webhook connection failed', [
           'error' => $e->getMessage(),
       ]);
       throw new WebhookDeliveryException($e->getMessage());
   }
   ```

4. **Track Delivery Attempts:**
   ```php
   $audit->increment('webhook_attempts');
   $audit->update([
       'last_webhook_attempt_at' => now(),
   ]);
   ```

**Acceptance Criteria:**
- [ ] 2xx responses mark webhook as delivered
- [ ] 4xx responses don't retry
- [ ] 5xx responses trigger retry
- [ ] Timeouts trigger retry
- [ ] Attempts tracked in audit record

---

### Task 5.2: Reduce Webhook Timeout

**Objective:** Prevent slow client endpoints from blocking workers.

**Implementation:**

1. **Set Aggressive Timeout:**
   ```env
   AUDITS_WEBHOOK_TIMEOUT=5  # Down from 30s
   ```

2. **Add Connect Timeout:**
   ```php
   Http::timeout(5)
       ->connectTimeout(2)  // Max 2s to establish connection
       ->post($webhookUrl, $payload);
   ```

3. **Document Timeout in API:**
   ```
   Webhook endpoints must respond within 5 seconds.
   If your endpoint is slow, acknowledge immediately and process async.

   Example:
   POST /webhook
   {
     "audit_id": "...",
     ...
   }

   Response (< 5s):
   {
     "acknowledged": true
   }

   Then process audit data asynchronously.
   ```

4. **Add Timeout Metrics:**
   - Track webhook response times
   - Alert if average > 3s
   - Provide to clients in dashboard

5. **Configuration Flexibility:**
   ```env
   AUDITS_WEBHOOK_TIMEOUT=5
   AUDITS_WEBHOOK_CONNECT_TIMEOUT=2
   ```

**Acceptance Criteria:**
- [ ] Default timeout reduced to 5s
- [ ] Connect timeout set to 2s
- [ ] Documented in API guide
- [ ] Metrics tracked
- [ ] Configurable via .env

---

### Task 5.3: Implement Webhook Retry Strategy

**Objective:** Define and document clear retry behavior.

**Implementation:**

1. **Retry Configuration:**
   ```php
   public int $tries = 5;  // Up from 2

   public function backoff(): array
   {
       return [30, 60, 300, 900];  // 30s, 1m, 5m, 15m
   }
   ```

2. **Exponential Backoff with Jitter:**
   ```php
   protected function getBackoffDelay(int $attempt): int
   {
       $base = [30, 60, 300, 900][$attempt - 1] ?? 900;
       $jitter = rand(0, $base * 0.2);  // ±20%
       return $base + $jitter;
   }
   ```

3. **Include Retry Info in Headers:**
   ```
   X-Webhook-Attempt: 3
   X-Webhook-Max-Attempts: 5
   X-Webhook-Next-Retry: 300  # seconds
   ```

4. **Document Retry Strategy:**
   In docs/api.md:
   ```markdown
   ## Webhook Retry Strategy

   Webhooks are retried up to 5 times with exponential backoff:

   - Attempt 1: Immediate
   - Attempt 2: 30 seconds later
   - Attempt 3: 1 minute later
   - Attempt 4: 5 minutes later
   - Attempt 5: 15 minutes later

   If all attempts fail, the webhook is marked as permanently failed.

   ### HTTP Status Codes
   - 2xx: Success, no retry
   - 4xx: Client error, no retry (check your endpoint)
   - 5xx: Server error, will retry
   - Timeout: Will retry
   ```

5. **Manual Retry Command:**
   ```bash
   php artisan webhook:retry {audit_id}
   php artisan webhook:retry-failed  # Retry all failed
   ```

**Acceptance Criteria:**
- [ ] Webhooks retry up to 5 times
- [ ] Exponential backoff implemented
- [ ] Retry headers included
- [ ] Strategy documented
- [ ] Manual retry command works

---

### Task 5.4: Create Webhook Delivery History

**Objective:** Track full webhook delivery history for debugging.

**Implementation:**

1. **Create `webhook_deliveries` Table:**
   ```
   - id
   - audit_id (foreign key)
   - attempt_number
   - url
   - payload (JSON)
   - response_status (nullable)
   - response_body (nullable, text)
   - response_time_ms (nullable)
   - error_message (nullable, text)
   - delivered_at (nullable)
   - created_at
   ```

2. **Record Each Attempt:**
   ```php
   WebhookDelivery::create([
       'audit_id' => $audit->id,
       'attempt_number' => $this->attempts(),
       'url' => $webhookUrl,
       'payload' => $payload,
       'response_status' => $response->status(),
       'response_body' => $response->body(),
       'response_time_ms' => $durationMs,
       'delivered_at' => $response->successful() ? now() : null,
   ]);
   ```

3. **Include in Audit Response:**
   ```
   GET /api/v1/audits/{id}

   {
     ...,
     "webhook_deliveries": [
       {
         "attempt": 1,
         "status": 500,
         "response_time_ms": 2341,
         "delivered_at": null,
         "error": "Internal Server Error",
         "created_at": "..."
       },
       {
         "attempt": 2,
         "status": 200,
         "response_time_ms": 145,
         "delivered_at": "2025-12-29T10:05:30Z",
         "created_at": "..."
       }
     ]
   }
   ```

4. **Add to Dashboard (if exists):**
   - Show delivery success rate
   - Show average response time
   - Show failed deliveries requiring attention

5. **Cleanup Old Deliveries:**
   ```bash
   php artisan webhook:prune-deliveries --days=30
   ```

**Acceptance Criteria:**
- [ ] All webhook attempts recorded
- [ ] Delivery history accessible via API
- [ ] Includes response status and body
- [ ] Cleanup command exists
- [ ] Useful for debugging

---

### Task 5.5: Implement Fallback Notifications

**Objective:** Notify admins when webhooks fail completely.

**Implementation:**

1. **Email Notification:**
   ```php
   // After final webhook failure
   Mail::to(config('audits.admin_email'))
       ->queue(new WebhookFailedNotification($audit));
   ```

2. **Slack Notification (Optional):**
   ```php
   if (config('audits.slack_webhook_url')) {
       Http::post(config('audits.slack_webhook_url'), [
           'text' => "Webhook failed for audit {$audit->id}",
           'attachments' => [
               'fallback' => 'Webhook failed',
               'color' => 'danger',
               'fields' => [
                   ['title' => 'Audit ID', 'value' => $audit->id],
                   ['title' => 'URL', 'value' => $audit->url],
                   ['title' => 'Attempts', 'value' => $audit->webhook_attempts],
               ],
           ],
       ]);
   }
   ```

3. **Admin Dashboard Alert:**
   - Create `failed_webhooks` queue
   - Show in admin panel (if exists)
   - Allow manual retry from UI

4. **Configuration:**
   ```env
   AUDITS_ADMIN_EMAIL=admin@example.com
   AUDITS_SLACK_WEBHOOK_URL=https://hooks.slack.com/...
   AUDITS_NOTIFY_ON_WEBHOOK_FAILURE=true
   ```

5. **Rate Limit Notifications:**
   - Don't send notification for every failure
   - Batch notifications (max 1/hour)
   - Or only notify on final failure

**Acceptance Criteria:**
- [ ] Email sent on webhook failure
- [ ] Slack notification optional
- [ ] Notifications rate-limited
- [ ] Configuration via .env
- [ ] Admin can disable notifications

---

## Risks and Dependencies

**Risks:**
- **Too many retries may delay notifications:** 5 attempts with backoff = max 21 minutes
- **Webhook history table growth:** Implement cleanup after 30 days
- **Email notifications could spam:** Rate limit to 1/hour

**Dependencies:**
- Mail configuration for email notifications
- Slack webhook URL for Slack notifications
- No other new dependencies

---

## Required Tests

### Response Validation Tests:
- [ ] 2xx responses don't retry
- [ ] 4xx responses don't retry
- [ ] 5xx responses retry
- [ ] Timeout triggers retry

### Retry Tests:
- [ ] Exponential backoff works
- [ ] Max attempts respected
- [ ] Manual retry command works

### Delivery History Tests:
- [ ] All attempts recorded
- [ ] History accessible via API
- [ ] Cleanup command works

### Fallback Tests:
- [ ] Email sent on final failure
- [ ] Slack notification works
- [ ] Notifications rate-limited

---

## Definition of Done

- [ ] All tasks implemented
- [ ] Response validation working
- [ ] Retry strategy implemented
- [ ] Delivery history tracked
- [ ] Fallback notifications working
- [ ] Tests passing
- [ ] Documentation updated

---

## Documentation to Update

1. **docs/api.md:**
   - Document webhook retry strategy
   - Document response expectations
   - Document webhook delivery history

2. **docs/webhooks.md (CREATE):**
   - How to implement webhook endpoint
   - Best practices for reliability
   - How to handle retries
   - How to debug delivery issues

3. **README.md:**
   - Mention webhook reliability features
   - Link to webhook documentation

---

## Effort Estimation

| Task | Estimate | Priority |
|------|----------|----------|
| 5.1 Response Validation | 3-4h | P0 |
| 5.2 Reduce Timeout | 2h | P0 |
| 5.3 Retry Strategy | 3-4h | P0 |
| 5.4 Delivery History | 4-6h | P1 |
| 5.5 Fallback Notifications | 3-4h | P1 |
| Migration | 1h | P0 |
| Testing | 4-5h | P0 |
| Documentation | 2-3h | P1 |

**Total:** 22-31 hours (3-4 days)
