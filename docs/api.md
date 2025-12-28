# API Reference

## Authentication

All API endpoints require Bearer token authentication via Laravel Sanctum.

```bash
curl -X POST https://audits.yoursite.com/api/v1/scan \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d @payload.json
```

---

## POST /api/v1/scan

Submit a PageSpeed Insights payload for PDF generation.

### Request

**Headers:**
- `Authorization: Bearer {token}` (required)
- `Content-Type: application/json`

**Body:** Raw PageSpeed Insights JSON response

```json
{
  "lighthouseResult": {
    "finalDisplayedUrl": "https://example.com",
    "categories": {
      "performance": { "score": 0.95 }
    },
    "audits": {
      "largest-contentful-paint": { "displayValue": "1.2 s" },
      "first-contentful-paint": { "displayValue": "0.8 s" },
      "cumulative-layout-shift": { "displayValue": "0.05" }
    }
  }
}
```

> **Note:** The endpoint also accepts array-wrapped payloads from n8n: `[{ "lighthouseResult": ... }]`

### Response

**202 Accepted**

```json
{
  "message": "Audit queued",
  "audit_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

**401 Unauthorized**

```json
{
  "message": "Unauthenticated."
}
```

---

## Webhook Callback

When PDF generation completes, a POST request is sent to `AUDITS_WEBHOOK_RETURN_URL`.

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
  }
}
```
