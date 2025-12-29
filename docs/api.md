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

Submit a URL or PageSpeed payload for PDF generation.

### Option 1: URL-based (Recommended)

Simply pass the URL and let the service fetch PageSpeed data automatically:

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

**What happens:**
1. Fetches PageSpeed data (Performance, SEO, Accessibility)
2. Takes desktop (1920x1080) and mobile (375x812) screenshots
3. Converts screenshots to optimized WebP (600px width)
4. Generates PDF with device mockups
5. Sends webhook callback with PDF URL

### Option 2: Payload-based (Legacy/n8n)

Send raw PageSpeed Insights JSON directly:

```json
{
  "lighthouseResult": {
    "finalDisplayedUrl": "https://example.com",
    "categories": {
      "performance": { "score": 0.95 },
      "seo": { "score": 0.92 },
      "accessibility": { "score": 0.88 }
    },
    "audits": {
      "largest-contentful-paint": { "displayValue": "1.2 s" },
      "first-contentful-paint": { "displayValue": "0.8 s" },
      "cumulative-layout-shift": { "displayValue": "0.05" }
    }
  },
  "lang": "pt_BR"
}
```

**Note:** Payload-based requests skip screenshot capture since the site is not actively fetched.

### Response

**202 Accepted**

```json
{
  "message": "Audit queued",
  "audit_id": "550e8400-e29b-41d4-a716-446655440000",
  "lang": "pt_BR",
  "strategy": "mobile"
}
```

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
  "audit_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "completed",
  "target_url": "https://example.com",
  "pdf_url": "https://audits.yoursite.com/storage/reports/550e8400.pdf",
  "score": 95,
  "metrics": {
    "lcp": "1.2 s",
    "fcp": "0.8 s",
    "cls": "0.05"
  }
}
```

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
