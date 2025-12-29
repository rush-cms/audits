<div align="center">
  <img src=".github/assets/audits-banner.webp" alt="Rush CMS Audits Banner" width="100%">

  # Rush CMS Audits Microservice

  [![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com)
  [![PHPStan Level 8](https://img.shields.io/badge/PHPStan-Level%208-4F5B93?style=flat&logo=php&logoColor=white)](https://phpstan.org)
  [![Tests Passing](https://img.shields.io/badge/Tests-24%20passed-00C853?style=flat&logo=pest&logoColor=white)](https://pestphp.com)
  [![License MIT](https://img.shields.io/badge/License-MIT-blue?style=flat)](LICENSE)

  **[rushcms.com/audits](https://rushcms.com/audits)**
</div>

<br>

A standalone, headless microservice dedicated to generating high-fidelity performance reports (Lighthouse/PageSpeed) in PDF format. Designed to be **whitelabel**, **asynchronous**, and **webhook-oriented**.

Ideally used with **n8n** or other automation tools to ingest PageSpeed API data and return professional client-ready PDFs.

> **Documentation:** [docs/](docs/README.md)

## Features

* **Headless Architecture:** API-first design with no frontend UI
* **Whitelabel Ready:** Customize logos, brand names, CTA links, and more via config
* **Asynchronous Processing:** Heavy PDF generation happens in the background via Queues
* **Webhook Callbacks:** Receive a ping with the PDF URL as soon as it's ready
* **Webhook Signatures:** HMAC-SHA256 signatures for webhook authenticity verification
* **Audit Persistence:** All audits stored in database with status tracking (pending, processing, completed, failed)
* **Idempotency Support:** Prevents duplicate audits within configurable time windows
* **Smart Pruning:** Auto-cleanup of old PDF files to save storage
* **Token Authentication:** Built-in console commands to manage API clients
* **Rate Limiting:** Configurable per-token and global rate limits with Redis
* **SSRF Protection:** Blocks private networks and localhost in production
* **Multi-language Support:** English, Portuguese (BR), and Spanish
* **Screenshot Capture:** Automatic desktop & mobile screenshots with device mockup frames
* **SEO & Accessibility Audits:** Optional sections with detailed issue reports
* **Core Web Vitals:** LCP, FCP, CLS with contextual performance messages

## Tech Stack

* **Core:** Laravel 12 API (Slim setup)
* **PDF Engine:** Spatie Browsershot (Puppeteer/Chromium)
* **Image Processing:** Spatie Image (WebP conversion)
* **Queue:** Redis (Recommended)
* **Storage:** Local Public Disk / S3

## Workflow

1. **Ingestion:** Send a URL to `POST /api/v1/scan`
2. **Idempotency Check:** Returns existing audit if duplicate within time window
3. **Database Persistence:** Creates audit record with status `pending`
4. **PageSpeed Fetch:** Fetches Performance, SEO & Accessibility data, marks audit as `processing`
5. **Screenshot Capture:** Takes desktop (1920x1080) and mobile (375x812) screenshots
6. **Image Optimization:** Converts screenshots to WebP (600px width)
7. **PDF Generation:** Renders Blade views with Tailwind CSS, converts to PDF
8. **Status Update:** Marks audit as `completed` with score, metrics, and PDF path
9. **Callback:** Sends POST request to your webhook URL with the PDF link and metadata

## Environment Configuration

```bash
cp .env.example .env
```

Edit the `.env` file to configure:

```ini
APP_URL=https://audits.rushcms.com
APP_TIMEZONE="America/Sao_Paulo"

# Service Logic
AUDITS_WEBHOOK_RETURN_URL="https://your-main-app.com/api/webhook/audit-ready"
AUDITS_CONCURRENCY=1
AUDITS_RETENTION_DAYS=7
AUDITS_IDEMPOTENCY_WINDOW=60

# Branding (Whitelabel)
AUDITS_BRAND_NAME="Rush CMS"
AUDITS_LOGO_PATH="rush-cms-logo.png"

# Report Settings
AUDITS_DATE_FORMAT="d/m/Y H:i"
AUDITS_SHOW_SEO=false
AUDITS_SHOW_ACCESSIBILITY=false
AUDITS_CTA_URL="https://wa.me/5511999999999"

# PageSpeed Insights API (optional, increases rate limits)
PAGESPEED_API_KEY=

# Browsershot / Puppeteer (Critical for Linux/Docker)
BROWSERSHOT_NODE_BINARY="/usr/bin/node"
BROWSERSHOT_NPM_BINARY="/usr/bin/npm"
BROWSERSHOT_CHROME_PATH="/usr/bin/google-chrome"
```

## Configuration Reference

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_TIMEZONE` | `America/Sao_Paulo` | Timezone for date display |
| `AUDITS_BRAND_NAME` | `Rush CMS` | Brand name in header/footer |
| `AUDITS_LOGO_PATH` | `null` | Path to logo in public folder |
| `AUDITS_DATE_FORMAT` | `d/m/Y H:i` | PHP date format for header |
| `AUDITS_SHOW_SEO` | `false` | Show SEO audit section |
| `AUDITS_SHOW_ACCESSIBILITY` | `false` | Show Accessibility section |
| `AUDITS_CTA_URL` | WhatsApp link | Call-to-action button URL |
| `AUDITS_RETENTION_DAYS` | `7` | Days to keep PDF files before pruning |
| `AUDITS_IDEMPOTENCY_WINDOW` | `60` | Minutes to prevent duplicate audits |
| `AUDITS_WEBHOOK_TIMEOUT` | `30` | Webhook timeout in seconds |
| `AUDITS_WEBHOOK_SECRET` | `null` | Secret for webhook HMAC signatures |
| `AUDITS_RATE_LIMIT_PER_MINUTE` | `60` | Requests per minute per token |
| `AUDITS_RATE_LIMIT_PER_HOUR` | `500` | Requests per hour per token |
| `AUDITS_RATE_LIMIT_PER_DAY` | `2000` | Requests per day per token |
| `AUDITS_RATE_LIMIT_GLOBAL_PER_MINUTE` | `200` | Global requests per minute |
| `PAGESPEED_API_KEY` | `null` | Google PageSpeed API key |

## API Reference

### Authentication

All requests require a Bearer Token:
```
Authorization: Bearer <your-token>
```

### Rate Limiting

API requests are rate-limited per token:
- **60 requests/minute** (default)
- **500 requests/hour** (default)
- **2000 requests/day** (default)
- **200 requests/minute globally** (all tokens combined)

Rate limit headers are included in all responses:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1704067200
```

When limit is exceeded, you'll receive a `429 Too Many Requests` response:
```json
{
  "message": "Too many requests. Please try again later.",
  "retry_after": 32
}
```

### Webhook Signatures

If `AUDITS_WEBHOOK_SECRET` is configured, webhooks include HMAC-SHA256 signatures for verification:

**Headers:**
```
X-Webhook-Signature: sha256=abc123...
X-Webhook-Timestamp: 1704067200
X-Webhook-ID: 20250129120000-a1b2c3d4e5f6g7h8
```

**Verification (PHP):**
```php
$secret = env('AUDITS_WEBHOOK_SECRET');
$timestamp = $_SERVER['HTTP_X_WEBHOOK_TIMESTAMP'];
$signature = str_replace('sha256=', '', $_SERVER['HTTP_X_WEBHOOK_SIGNATURE']);
$payload = file_get_contents('php://input');

$expectedSignature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}

if (abs(time() - $timestamp) > 300) {
    http_response_code(401);
    exit('Timestamp expired');
}
```

**Verification (Node.js):**
```javascript
const crypto = require('crypto');

const secret = process.env.AUDITS_WEBHOOK_SECRET;
const timestamp = req.headers['x-webhook-timestamp'];
const signature = req.headers['x-webhook-signature'].replace('sha256=', '');
const payload = JSON.stringify(req.body);

const expectedSignature = crypto
  .createHmac('sha256', secret)
  .update(`${timestamp}.${payload}`)
  .digest('hex');

if (!crypto.timingSafeEqual(Buffer.from(signature), Buffer.from(expectedSignature))) {
  return res.status(401).json({ error: 'Invalid signature' });
}

if (Math.abs(Date.now() / 1000 - timestamp) > 300) {
  return res.status(401).json({ error: 'Timestamp expired' });
}
```

### Security & SSRF Protection

In production (`APP_ENV=production`), the service automatically blocks:
- Private IP ranges (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`)
- Localhost (`127.0.0.1`, `::1`, `localhost`)
- Link-local addresses (`169.254.0.0/16` - AWS metadata, etc.)
- Custom blocked domains (via `config/blocked-domains.php`)

**Example blocked URLs in production:**
```
http://localhost:8000/admin          ❌ Blocked
http://192.168.1.1/                   ❌ Blocked
http://169.254.169.254/latest/meta-data/ ❌ Blocked (AWS metadata)
https://example.com                   ✅ Allowed
```

In local/development (`APP_ENV=local`), SSRF protection is **disabled** to allow testing against local services.

**Blocking Custom Domains:**

Edit `config/blocked-domains.php` to add domains:
```php
return [
    'internal.company.com',
    'staging.myapp.com',
    'localhost.example.com',
];
```

Subdomains are automatically matched (e.g., `example.com` blocks `www.example.com`).

### Submit Scan

**Endpoint:** `POST /api/v1/scan`

**Request:**
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

**Response:** `202 Accepted`
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

**Note:** Duplicate requests within the idempotency window return the same `audit_id` without triggering new processing.

### Get Audit Status

**Endpoint:** `GET /api/v1/audits/{id}`

**Response:** `200 OK`
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
  "pdf_url": "https://audits.rushcms.com/storage/reports/550e8400.pdf",
  "error_message": null,
  "created_at": "2025-12-29T04:00:00Z",
  "completed_at": "2025-12-29T04:02:15Z"
}
```

Status values: `pending`, `processing`, `completed`, `failed`

### Get Stats

**Endpoint:** `GET /api/v1/stats`

**Response:** `200 OK`
```json
{
  "minute": 3,
  "hour": 45,
  "day": 127,
  "month": 1542
}
```

> **Full API Documentation:** [docs/api.md](docs/api.md)

## Console Commands

```bash
# Create API token
php artisan audit:create-token "Client Name"

# Test PDF generation (no API calls)
php artisan test:pdf --lang=pt_BR

# Prune old PDFs
php artisan audit:prune-pdfs

# Check browser setup
php artisan audit:check-browser
```

## Deployment

### System Requirements

- PHP 8.4+
- Node.js 18+
- Chromium/Chrome
- Redis (for queues)

### Queue Worker (Required)

```bash
php artisan queue:work --tries=2 --timeout=180
```

### Scheduler

```bash
php artisan schedule:run
```

## Report Components

The PDF report includes:

1. **Header:** Brand logo + generation timestamp
2. **Performance Score:** Circular gauge with pass/fail indicator
3. **Device Mockup:** iPhone 16 + MacBook frames with live screenshots
4. **Core Web Vitals:** LCP, FCP, CLS cards with progress bars
5. **Performance Messages:** Contextual feedback based on metrics
6. **SEO Section:** Score + failed audit list (optional)
7. **Accessibility Section:** Score + failed audit list (optional)
8. **Closing CTA:** Dynamic message based on score + WhatsApp button
9. **Footer:** Brand name, Audit ID, data source

## License

This project is open-sourced software licensed under the **[MIT License](LICENSE)**.