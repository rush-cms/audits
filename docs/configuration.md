# Configuration

All configuration is done via environment variables in `.env`.

## Core Settings

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_URL` | Base URL of the service | `http://localhost` |
| `QUEUE_CONNECTION` | Queue driver | `redis` |

## Audit Service

| Variable | Description | Default |
|----------|-------------|---------|
| `AUDITS_WEBHOOK_RETURN_URL` | URL to receive completion callbacks | (required) |
| `AUDITS_RETENTION_DAYS` | Days to keep PDFs before pruning | `7` |

## Whitelabel / Branding

| Variable | Description | Default |
|----------|-------------|---------|
| `AUDITS_BRAND_NAME` | Brand name in PDF | `Rush CMS` |
| `AUDITS_LOGO_URL` | Logo URL for PDF header | (Rush CMS logo) |

## Browsershot / Chrome

| Variable | Description | Default |
|----------|-------------|---------|
| `BROWSERSHOT_NODE_BINARY` | Path to Node.js | `/usr/bin/node` |
| `BROWSERSHOT_NPM_BINARY` | Path to NPM | `/usr/bin/npm` |
| `BROWSERSHOT_CHROME_PATH` | Path to Chrome/Chromium | `/usr/bin/google-chrome` |
| `BROWSERSHOT_TIMEOUT` | Process timeout in seconds | `60` |
| `BROWSERSHOT_MEMORY_LIMIT` | Max memory per process (MB) | `512` |
| `BROWSERSHOT_MAX_CONCURRENT_PDF` | Max concurrent PDF processes | `3` |
| `BROWSERSHOT_MAX_CONCURRENT_SCREENSHOTS` | Max concurrent screenshot processes | `5` |

## Screenshot Management

| Variable | Description | Default |
|----------|-------------|---------|
| `AUDITS_DELETE_SCREENSHOTS_AFTER_PDF` | Delete screenshots after PDF generation | `true` |
| `AUDITS_ORPHANED_SCREENSHOTS_RETENTION_HOURS` | Hours before orphaned screenshots are deleted | `24` |

## PageSpeed API

| Variable | Description | Default |
|----------|-------------|---------|
| `PAGESPEED_API_KEY` | Google PageSpeed API key (optional) | - |
| `PAGESPEED_RATE_LIMIT_PER_MINUTE` | API calls per minute | `6` |
| `PAGESPEED_RATE_LIMIT_PER_DAY` | API calls per day | `25000` |

## Queue Limits

| Variable | Description | Default |
|----------|-------------|---------|
| `QUEUE_PDF_CONCURRENCY` | Max concurrent PDF generation jobs | `3` |
| `QUEUE_SCREENSHOT_CONCURRENCY` | Max concurrent screenshot capture jobs | `5` |
| `QUEUE_MAX_DEPTH_ALERT` | Queue depth threshold for alerts | `100` |

## Request Validation

| Variable | Description | Default |
|----------|-------------|---------|
| `API_MAX_REQUEST_SIZE` | Max request payload size in bytes | `1048576` (1MB) |

## Rate Limiting

| Variable | Description | Default |
|----------|-------------|---------|
| `AUDITS_RATE_LIMIT_PER_MINUTE` | Requests per minute per token | `60` |
| `AUDITS_RATE_LIMIT_PER_HOUR` | Requests per hour per token | `500` |
| `AUDITS_RATE_LIMIT_PER_DAY` | Requests per day per token | `2000` |
| `AUDITS_RATE_LIMIT_GLOBAL_PER_MINUTE` | Global requests per minute | `200` |

## Example .env

```env
APP_URL=https://audits.yoursite.com
QUEUE_CONNECTION=redis

# Audit Service
AUDITS_WEBHOOK_RETURN_URL=https://app.yoursite.com/api/webhook/audit
AUDITS_RETENTION_DAYS=7

# Branding
AUDITS_BRAND_NAME="Your Brand"
AUDITS_LOGO_URL="https://yoursite.com/logo.png"

# Browsershot
BROWSERSHOT_NODE_BINARY=/usr/bin/node
BROWSERSHOT_NPM_BINARY=/usr/bin/npm
BROWSERSHOT_CHROME_PATH=/usr/bin/chromium
```
