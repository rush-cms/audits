# API Reference

## Authentication

All API endpoints require Bearer token authentication via Laravel Sanctum.

```bash
curl -X POST https://audits.yoursite.com/api/v1/scan \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"url": "https://example.com"}'
```

---

## POST /api/v1/scan

Submit a URL or PageSpeed payload for PDF generation.

### Option 1: URL-based (Recommended)

Simply pass the URL and let the service fetch PageSpeed data:

```json
{
  "url": "https://example.com",
  "lang": "en",
  "strategy": "mobile"
}
```

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `url` | string | required | URL to analyze |
| `lang` | string | `en` | Language: `en`, `pt_BR`, `es` |
| `strategy` | string | `mobile` | Strategy: `mobile` or `desktop` |

### Option 2: Payload-based (Legacy)

Send raw PageSpeed Insights JSON:

```json
{
  "lighthouseResult": {
    "finalDisplayedUrl": "https://example.com",
    "categories": { "performance": { "score": 0.95 } },
    "audits": {
      "largest-contentful-paint": { "displayValue": "1.2 s" },
      "first-contentful-paint": { "displayValue": "0.8 s" },
      "cumulative-layout-shift": { "displayValue": "0.05" }
    }
  }
}
```

### Response

**202 Accepted**

```json
{
  "message": "Audit queued",
  "audit_id": "550e8400-e29b-41d4-a716-446655440000",
  "lang": "en",
  "strategy": "mobile"
}
```

---

## GET /api/v1/stats

Get scan counts for current time periods.

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
