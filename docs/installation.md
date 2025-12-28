# Installation

## Requirements

- PHP 8.2+
- Node.js 18+
- Chromium/Google Chrome
- Redis (for queues)
- Composer
- NPM

## Steps

### 1. Clone and Install

```bash
git clone https://github.com/your-org/rush-cms-audits.git
cd rush-cms-audits

composer install
npm install
```

### 2. Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database

```bash
php artisan migrate
```

### 4. Storage

```bash
php artisan storage:link
```

### 5. Verify Browser

```bash
php artisan audit:check-browser
```

If this fails, ensure Chrome/Chromium is installed and paths are correct in `.env`.

### 6. Create API Token

```bash
php artisan audit:create-token "n8n-integration"
```

Save the token - it won't be shown again.

### 7. Start Queue Worker

```bash
php artisan queue:work
```

For production, use Supervisor to keep the worker running.

## Docker (Optional)

```dockerfile
# Example Dockerfile additions for Chrome
RUN apt-get update && apt-get install -y \
    chromium \
    --no-install-recommends
```

Set in `.env`:
```
BROWSERSHOT_CHROME_PATH=/usr/bin/chromium
```
