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
