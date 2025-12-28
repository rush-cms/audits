# Rush CMS Audits Microservice

> **[audits.rushcms.com](https://audits.rushcms.com)**

A standalone, headless microservice dedicated to generating high-fidelity performance reports (Lighthouse/PageSpeed) in PDF format. Designed to be **whitelabel**, **asynchronous**, and **webhook-oriented**.

Ideally used with **n8n** or other automation tools to ingest PageSpeed API data and return professional client-ready PDFs.

## Features

* **Headless Architecture:** API-first design with no frontend UI
* **Whitelabel Ready:** Customize logos, brand names, and footer text via config
* **Asynchronous Processing:** Heavy PDF generation happens in the background via Queues
* **Webhook Callbacks:** Receive a ping with the PDF URL as soon as it's ready
* **Smart Pruning:** Auto-cleanup of old PDF files to save storage
* **Token Authentication:** Built-in console commands to manage API clients

## Tech Stack

* **Core:** Laravel 12 API (Slim setup)
* **PDF Engine:** Spatie Browsershot (Puppeteer/Chromium)
* **Charts:** QuickChart.io (No local node chart rendering required)
* **Queue:** Redis (Recommended)
* **Storage:** Local Public Disk / S3

## Workflow

The service acts as a "middleman" between raw data and a presentation-ready document.

1. **Ingestion:** You send raw JSON from the Google PageSpeed Insights API to `POST /api/v1/scan`.
    *Tip: Use an n8n node to fetch the Google API data and forward it here.*
2. **Queue:** The payload is validated and dispatched to `GenerateAuditPdfJob`.
3. **Processing:**
    * Extracts key metrics (LCP, FCP, CLS, Score).
    * Renders a Blade View styled with Tailwind CSS (Whitelabel).
    * Converts HTML to PDF using a headless Chrome instance.
    * Uploads the file to public storage.
4. **Callback:** The system sends a POST request to your `AUDITS_WEBHOOK_RETURN_URL` with the final PDF URL.

## Environment Configuration
```bash
cp .env.example .env
``` 

Edit the `.env` file to configure:

```ini
APP_URL=https://audits.rushcms.com

# Service Logic
AUDITS_WEBHOOK_RETURN_URL="https://your-main-app.com/api/webhook/audit-ready"
AUDITS_CONCURRENCY=1
AUDITS_RETENTION_DAYS=7

# Branding (Whitelabel)
AUDITS_BRAND_NAME="Rush CMS"
AUDITS_LOGO_URL="https://rushcms.com/assets/logo-dark.png"

# Browsershot / Puppeteer (Critical for Linux/Docker)
# Verify paths with: which node / which google-chrome
BROWSERSHOT_NODE_BINARY="/usr/bin/node"
BROWSERSHOT_NPM_BINARY="/usr/bin/npm"
BROWSERSHOT_CHROME_PATH="/usr/bin/google-chrome"
```

## API Reference

### Authentication
All requests require a Bearer Token.
`Authorization: Bearer <your-token>`

### 1. Submit Scan
Queues a new PDF generation job.

* **Endpoint:** `POST /api/v1/scan`
* **Headers:** `Content-Type: application/json`
* **Body:** The **raw JSON object** directly from Google PageSpeed Insights.

**Response:**

```json
{
    "message": "Audit queued",
    "audit_id": "98a3b2-..."
}
```

### 2. Webhook Callback (Incoming)
When processing is done, your `AUDITS_WEBHOOK_RETURN_URL` will receive:

```json
{
  "audit_id": "98a3b2-...",
  "status": "completed",
  "target_url": "https://client-site.com",
  "pdf_url": "https://audits.rushcms.com/storage/reports/98a3b2.pdf",
  "score": 85,
  "metrics": {
    "lcp": "2.1s",
    "fcp": "1.0s",
    "cls": "0.05"
  }
}
```

## Console Management
Since this is a headless service, use custom Artisan commands to manage it.

### Manage Clients (Auth)
Create tokens for your main application or external clients.

```bash
# Syntax: php artisan audit:create-token <name>
php artisan audit:create-token "Main App Production"

# Output: Token created: 1|laravel_sanctum_token...
# (Save this token immediately)
```

### Maintenance
```bash
# Verify if Puppeteer can launch Chrome
php artisan audit:check-browser

# Force delete old PDFs (Runs via Schedule automatically)
php artisan audit:prune-pdfs
```

## Deployment

### Docker / System Requirements
This project relies on **Puppeteer**, which requires a valid Chromium/Chrome instance and Node.js.

1. **Dockerfile:** Ensure your image installs Chromium dependencies (e.g., `libnss3`, `libatk`, `libx11`, etc.) or use a pre-built image like `keyer/laravel-browsershot`.
2. **Queues:** A worker is **mandatory**.
```bash
php artisan queue:work --tries=2 --timeout=120
```

3. **Scheduler:** Enable the scheduler to run the auto-pruning task.
```bash
php artisan schedule:run
```

### Whitelabel Customization
Edit `config/audits.php` or publish the views to customize the PDF layout:

```bash
php artisan vendor:publish --tag=audit-views
```