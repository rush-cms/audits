# API Reference

## Authentication

All API endpoints require Bearer token authentication via Laravel Sanctum.

```bash
curl -X POST https://audits.yoursite.com/api/v1/scan \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"url": "https://example.com", "lang": "pt_BR"}'
```

You can get a token by running the following command:

```bash
php artisan audit:create-token "My Token"
```

---

## POST /api/v1/scan

Submit a URL for PageSpeed analysis and PDF generation.

### Request Body

```json
{
  "url": "https://example.com",
  "lang": "pt_BR",
  "strategy": "mobile"
}
```

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `url` | string | required | URL to analyze |
| `lang` | string | `en` | Language: `en`, `pt_BR`, `es` |
| `strategy` | string | `mobile` | Strategy: `mobile` or `desktop` |

### Processing Pipeline

1. **Fetch PageSpeed Data** - Retrieves Performance, SEO, and Accessibility metrics
2. **Capture Screenshots** - Desktop (1920x1080) and mobile (375x812) screenshots
3. **Optimize Images** - Converts to WebP format (600px width)
4. **Generate PDF** - Creates report with device mockups and metrics
5. **Webhook Callback** - Sends notification with PDF URL (if configured)

### State-Based Idempotency

The API implements smart state-based idempotency to prevent unnecessary duplicate audits:

**Behavior by Audit Status:**
- **Pending/Processing:** Returns existing `audit_id` (audit still in progress)
- **Completed:** Creates **new** audit (allows immediate re-scan of updated sites)
- **Failed (recent):** Returns existing `audit_id` if failed < 5 minutes ago (retry window)
- **Failed (old):** Creates **new** audit if failed >= 5 minutes ago (allows retry)

**Configuration:**
- `AUDITS_FAILED_RETRY_AFTER=300` - Seconds before failed audit allows new attempt (default: 5 min)

**Why State-Based?**
- No clock skew issues between servers
- Clients can re-scan completed audits immediately
- Failed audits have smart retry window
- No arbitrary time windows blocking legitimate requests

### Response

**202 Accepted**

```json
{
  "message": "Audit queued",
  "audit_id": "550e8400-e29b-41d4-a716-446655440000",
  "url": "https://example.com",
  "lang": "pt_BR",
  "strategy": "mobile",
  "status": "pending"
}
```

---

## GET /api/v1/audits/{id}

Retrieve the status and results of a specific audit.

### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | UUID | Audit ID returned from POST /scan |

### Response

**200 OK**

```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "url": "https://example.com",
  "strategy": "mobile",
  "lang": "pt_BR",
  "status": "completed",
  "score": 95,
  "metrics": {
    "lcp": "1.2 s",
    "fcp": "0.8 s",
    "cls": "0.05"
  },
  "pdf_url": "https://audits.yoursite.com/storage/reports/550e8400.pdf",
  "error_message": null,
  "created_at": "2025-12-29T04:00:00Z",
  "completed_at": "2025-12-29T04:02:15Z"
}
```

### Status Values

| Status | Description |
|--------|-------------|
| `pending` | Audit created, waiting for processing |
| `processing` | PageSpeed data is being fetched |
| `completed` | PDF generated successfully |
| `failed` | An error occurred during processing |

**Note:** For failed audits, check the `error_message` field for details.

---

## GET /api/v1/stats

Get scan counts for current time periods. Useful for monitoring API usage.

### Response

**200 OK**

```json
{
  "minute": 3,
  "hour": 45,
  "day": 127,
  "month": 1542
}
```

---

## Webhook Callback

When PDF generation completes, a POST request is sent to `AUDITS_WEBHOOK_RETURN_URL` (if configured).

### Webhook Payload

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

**New Fields (Sprint 2):**
- `screenshotsIncluded` (boolean): Whether screenshots are present in the PDF
- `screenshotError` (string|null): Error message if screenshots failed (only if `AUDITS_REQUIRE_SCREENSHOTS=false`)

**Note:** When `AUDITS_REQUIRE_SCREENSHOTS=false` (default), the PDF is generated even if screenshots fail. The webhook will include `screenshotsIncluded: false` and a descriptive error message.

### Webhook Configuration

- **URL:** Set via `AUDITS_WEBHOOK_RETURN_URL` environment variable
- **Timeout:** Configurable via `AUDITS_WEBHOOK_TIMEOUT` (default: 30 seconds)
- **Retries:** Failed webhooks are retried up to 2 times via job queue
- **HTTP Method:** POST with JSON payload

---

## Error Responses

### 401 Unauthorized

Missing or invalid Bearer token.

```json
{
  "message": "Unauthenticated."
}
```

### 422 Unprocessable Entity

Invalid URL or malformed payload.

```json
{
  "error": "Invalid URL"
}
```

---

## Rate Limits

### Without API Key
- ~6 requests/minute to PageSpeed API

### With API Key (`PAGESPEED_API_KEY`)
- ~400 requests/day (standard quota)
- ~25,000 requests/day (elevated quota)

---

## Languages Supported

| Code | Language |
|------|----------|
| `en` | English |
| `pt_BR` | Portuguese (Brazil) |
| `es` | Spanish |

---

## Testing Locally

Use the artisan command to generate PDFs without API calls:

```bash
# Generate test PDF with mock data
php artisan test:pdf

# Specify language
php artisan test:pdf --lang=en
php artisan test:pdf --lang=pt_BR
php artisan test:pdf --lang=es
```
