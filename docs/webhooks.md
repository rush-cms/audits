# Webhook Implementation Guide

This guide covers everything you need to know about implementing and debugging webhooks with Rush CMS Audits.

---

## Quick Start

### 1. Configure Webhook URL

```bash
AUDITS_WEBHOOK_RETURN_URL=https://your-app.com/api/webhooks/audit-ready
```

### 2. Implement Endpoint

```php
Route::post('/api/webhooks/audit-ready', function (Request $request) {
    $validated = $request->validate([
        'auditId' => 'required|uuid',
        'pdfUrl' => 'required|url',
        'score' => 'required|integer',
    ]);

    // Process audit...

    return response()->json(['acknowledged' => true]);
});
```

### 3. Test

```bash
php artisan test:pdf
# Triggers webhook delivery to configured URL
```

---

## Webhook Payload Structure

### Successful Audit

```json
{
  "auditId": "550e8400-e29b-41d4-a716-446655440000",
  "status": "completed",
  "targetUrl": "https://example.com",
  "pdfUrl": "https://audits.yoursite.com/storage/reports/550e8400.pdf",
  "score": 95,
  "metrics": {
    "lcp": "1.2 s",
    "fcp": "0.8 s",
    "cls": "0.05"
  },
  "strategy": "mobile",
  "lang": "pt_BR",
  "screenshotsIncluded": true,
  "screenshotError": null
}
```

### Audit with Failed Screenshots

When `AUDITS_REQUIRE_SCREENSHOTS=false`, PDFs are generated even if screenshots fail:

```json
{
  "auditId": "...",
  "status": "completed",
  "screenshotsIncluded": false,
  "screenshotError": "Failed to capture screenshot: Connection timeout"
}
```

---

## HTTP Headers

### Request Headers (Sent to Your Endpoint)

```
Content-Type: application/json
X-Webhook-Attempt: 1
X-Webhook-Max-Attempts: 5
X-Webhook-Signature: sha256=abc123...
X-Webhook-Timestamp: 1704067200
X-Webhook-ID: 20250129120000-a1b2c3d4e5f6g7h8
```

### Response Headers (Your Endpoint Should Return)

```
HTTP/1.1 200 OK
Content-Type: application/json
```

---

## Retry Strategy

### Retry Schedule

| Attempt | Delay | Total Elapsed |
|---------|-------|---------------|
| 1 | Immediate | 0s |
| 2 | 30s ± 6s | ~30s |
| 3 | 60s ± 12s | ~1.5m |
| 4 | 300s ± 60s | ~6.5m |
| 5 | 900s ± 180s | ~21.5m |

Jitter (±20%) prevents thundering herd when multiple webhooks fail simultaneously.

### HTTP Status Code Behavior

```
2xx (200-299) → Success, no retry
4xx (400-499) → Client error, NO RETRY
5xx (500-599) → Server error, WILL RETRY
Timeout        → WILL RETRY
Connection Error → WILL RETRY
```

**Important:** Return 4xx for permanent errors (invalid auth, bad data) to avoid unnecessary retries.

---

## Endpoint Requirements

### Performance

Your endpoint **must respond within 5 seconds**.

**Bad (will timeout):**
```php
Route::post('/webhook', function (Request $request) {
    // DON'T do heavy processing synchronously
    $audit = Audit::create($request->all());
    $audit->generateReport(); // 30 seconds
    $audit->sendEmails();     // 10 seconds

    return response()->json(['ok' => true]); // Too late!
});
```

**Good (acknowledge + queue):**
```php
Route::post('/webhook', function (Request $request) {
    $validated = $request->validate([
        'auditId' => 'required|uuid',
        'pdfUrl' => 'required|url',
    ]);

    // Queue heavy work
    ProcessAuditJob::dispatch($validated);

    // Respond immediately
    return response()->json(['acknowledged' => true]);
});
```

### Idempotency

Your endpoint should handle duplicate deliveries gracefully.

**Why duplicates happen:**
- Network issues causing retries
- Manual webhook retry commands
- Job queue retries

**Handle with idempotency keys:**

```php
Route::post('/webhook', function (Request $request) {
    $auditId = $request->input('auditId');

    // Check if already processed
    if (ProcessedWebhook::where('audit_id', $auditId)->exists()) {
        return response()->json(['acknowledged' => true]);
    }

    // Mark as processed
    ProcessedWebhook::create(['audit_id' => $auditId]);

    // Process webhook...
    ProcessAuditJob::dispatch($request->all());

    return response()->json(['acknowledged' => true]);
});
```

---

## Security: Webhook Signatures

### Verify Signatures

Protect your endpoint from unauthorized requests:

```php
Route::post('/webhook', function (Request $request) {
    $secret = env('AUDITS_WEBHOOK_SECRET');
    $timestamp = $request->header('X-Webhook-Timestamp');
    $signature = str_replace('sha256=', '', $request->header('X-Webhook-Signature'));
    $payload = $request->getContent();

    // Verify signature
    $expectedSignature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

    if (!hash_equals($expectedSignature, $signature)) {
        abort(401, 'Invalid signature');
    }

    // Verify timestamp (prevent replay attacks)
    if (abs(time() - $timestamp) > 300) { // 5 minutes
        abort(401, 'Timestamp expired');
    }

    // Process webhook...
});
```

**Configuration:**

```bash
AUDITS_WEBHOOK_SECRET=your-secret-key-here
```

Generate secure secret:

```bash
openssl rand -base64 32
```

---

## Debugging Failed Webhooks

### Check Delivery History

View all delivery attempts:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  https://audits.yoursite.com/api/v1/audits/550e8400-...
```

Response includes `webhook_deliveries`:

```json
{
  "webhook_deliveries": [
    {
      "attempt": 1,
      "status": 500,
      "response_time_ms": 2341,
      "response_body": "{\"error\":\"Database connection failed\"}",
      "delivered_at": null,
      "created_at": "2025-12-29T10:00:00Z"
    }
  ]
}
```

### Check Logs

```bash
# Webhook-specific logs
tail -f storage/logs/webhooks.log

# All logs
tail -f storage/logs/laravel.log
```

Log entries include:
- Audit ID
- HTTP status code
- Response time
- Error messages
- Retry information

### Manual Retry

```bash
# Retry specific audit
php artisan webhook:retry 550e8400-e29b-41d4-a716-446655440000

# Retry all failed webhooks
php artisan webhook:retry-failed
```

---

## Common Issues

### Issue: Webhook Not Sent

**Cause:** `AUDITS_WEBHOOK_RETURN_URL` not configured

**Solution:**

```bash
# .env
AUDITS_WEBHOOK_RETURN_URL=https://your-app.com/webhook
```

### Issue: Timeouts

**Cause:** Your endpoint takes > 5 seconds to respond

**Solution:**
- Queue heavy processing
- Return response immediately
- Optimize database queries

### Issue: 4xx Errors (No Retries)

**Cause:** Authentication, validation, or routing errors

**Solution:**
- Check endpoint authentication
- Verify payload validation
- Test locally with ngrok

### Issue: 5xx Errors (Retries Exhausted)

**Cause:** Server errors, database issues, exceptions

**Solution:**
- Check application logs
- Fix underlying error
- Manually retry: `php artisan webhook:retry {audit_id}`

### Issue: No Notifications on Failure

**Cause:** Notifications not configured

**Solution:**

```bash
# .env
AUDITS_NOTIFY_ON_WEBHOOK_FAILURE=true
AUDITS_ADMIN_EMAIL=admin@example.com
AUDITS_SLACK_WEBHOOK_URL=https://hooks.slack.com/...
```

---

## Testing Locally

### Using ngrok

1. **Start ngrok:**

```bash
ngrok http 8000
```

2. **Configure webhook URL:**

```bash
# .env
AUDITS_WEBHOOK_RETURN_URL=https://abc123.ngrok.io/api/webhook
```

3. **Trigger webhook:**

```bash
php artisan test:pdf
```

4. **Check ngrok inspector:**

Visit http://127.0.0.1:4040 to see webhook requests.

### Using RequestBin

1. Create temporary endpoint at https://requestbin.com
2. Configure webhook URL to RequestBin URL
3. Trigger webhook
4. Inspect payload in RequestBin

---

## Production Best Practices

### 1. Use HTTPS

Webhooks contain sensitive data (audit results, URLs). Always use HTTPS.

### 2. Verify Signatures

Prevent unauthorized webhook calls:

```bash
AUDITS_WEBHOOK_SECRET=$(openssl rand -base64 32)
```

### 3. Monitor Failures

Set up alerts for webhook failures:

```bash
AUDITS_ADMIN_EMAIL=devops@example.com
AUDITS_SLACK_WEBHOOK_URL=https://hooks.slack.com/...
```

### 4. Implement Idempotency

Track processed webhooks to handle duplicates.

### 5. Queue Processing

Never block webhook response with heavy work.

### 6. Set Reasonable Timeouts

Your endpoint should respond in < 2 seconds ideally.

### 7. Log Everything

Log all webhook deliveries for debugging:

```php
Log::info('Webhook received', [
    'audit_id' => $request->input('auditId'),
    'score' => $request->input('score'),
]);
```

---

## Configuration Reference

```bash
# Webhook URL (required)
AUDITS_WEBHOOK_RETURN_URL=https://your-app.com/webhook

# Timeouts
AUDITS_WEBHOOK_TIMEOUT=5          # Response timeout (seconds)
AUDITS_WEBHOOK_CONNECT_TIMEOUT=2  # Connection timeout (seconds)

# Retry behavior
AUDITS_WEBHOOK_MAX_ATTEMPTS=5     # Max retry attempts

# Security
AUDITS_WEBHOOK_SECRET=your-secret-key

# Notifications
AUDITS_NOTIFY_ON_WEBHOOK_FAILURE=true
AUDITS_ADMIN_EMAIL=admin@example.com
AUDITS_SLACK_WEBHOOK_URL=https://hooks.slack.com/...
```

---

## Need Help?

If webhooks aren't working as expected:

1. Check delivery history: `GET /api/v1/audits/{id}`
2. Review logs: `storage/logs/webhooks.log`
3. Test locally with ngrok
4. Verify endpoint responds < 5s
5. Check signature verification
6. Manual retry: `php artisan webhook:retry {id}`

For persistent issues, enable debug logging:

```bash
# .env
LOG_LEVEL=debug
```
