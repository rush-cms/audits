# Rush CMS Audits Microservice

> **[audits.rushcms.com](https://audits.rushcms.com)**

A standalone, headless microservice dedicated to generating high-fidelity performance reports (Lighthouse/PageSpeed) in PDF format. Designed to be **whitelabel**, **asynchronous**, and **webhook-oriented**.

Ideally used with **n8n** or other automation tools to ingest PageSpeed API data and return professional client-ready PDFs.

> **Documentation:** [docs/](docs/README.md)

## Features

* **Headless Architecture:** API-first design with no frontend UI
* **Whitelabel Ready:** Customize logos, brand names, CTA links, and more via config
* **Asynchronous Processing:** Heavy PDF generation happens in the background via Queues
* **Webhook Callbacks:** Receive a ping with the PDF URL as soon as it's ready
* **Smart Pruning:** Auto-cleanup of old PDF files to save storage
* **Token Authentication:** Built-in console commands to manage API clients
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

1. **Ingestion:** Send a URL or raw PageSpeed JSON to `POST /api/v1/scan`
2. **PageSpeed Fetch:** If URL provided, fetches Performance, SEO & Accessibility data
3. **Screenshot Capture:** Takes desktop (1920x1080) and mobile (375x812) screenshots
4. **Image Optimization:** Converts screenshots to WebP (600px width)
5. **PDF Generation:** Renders Blade views with Tailwind CSS, converts to PDF
6. **Callback:** Sends POST request to your webhook URL with the PDF link

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
| `PAGESPEED_API_KEY` | `null` | Google PageSpeed API key |

## API Reference

### Authentication

All requests require a Bearer Token:
```
Authorization: Bearer <your-token>
```

### Submit Scan

**Endpoint:** `POST /api/v1/scan`

**Option 1: URL-based (Recommended)**
```json
{
  "url": "https://example.com",
  "lang": "pt_BR",
  "strategy": "mobile"
}
```

**Option 2: Payload-based**
```json
{
  "lighthouseResult": { ... }
}
```

**Response:** `202 Accepted`
```json
{
  "message": "Audit queued",
  "audit_id": "98a3b2-...",
  "lang": "pt_BR",
  "strategy": "mobile"
}
```

### Get Stats

**Endpoint:** `GET /api/v1/stats`

```json
{
  "minute": 3,
  "hour": 45,
  "day": 127,
  "month": 1542
}
```

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

This project is open-sourced software licensed under the **[GNU Affero General Public License v3.0 (AGPLv3)](LICENSE)**.