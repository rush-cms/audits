# Performance and Resource Limits

This document explains the resource limits and performance optimizations implemented in the service.

## Overview

The service implements multiple layers of resource protection to prevent crashes, manage costs, and ensure stable operation under load.

## Browsershot Resource Limits

Chrome/Chromium processes can consume significant resources. We limit them to prevent server crashes.

### Configuration

```env
BROWSERSHOT_TIMEOUT=60                      # Kill process after 60 seconds
BROWSERSHOT_MEMORY_LIMIT=512                # 512MB max per process
BROWSERSHOT_MAX_CONCURRENT_PDF=3            # Max 3 PDF generations at once
BROWSERSHOT_MAX_CONCURRENT_SCREENSHOTS=5    # Max 5 screenshot captures at once
```

### How It Works

**Timeout Protection:**
- Each Browsershot process is killed after 60 seconds
- Prevents runaway processes from consuming resources indefinitely
- Jobs automatically retry with exponential backoff

**Memory Limits:**
- Chrome is launched with `--max-old-space-size=512`
- Prevents individual processes from using excessive RAM
- Combined with `--disable-dev-shm-usage` to reduce shared memory usage

**Concurrency Limits:**
- Queue middleware enforces max 3 PDF generations per minute
- Queue middleware enforces max 5 screenshot captures per minute
- Jobs wait in queue until a slot becomes available
- Uses Laravel's `RateLimited` middleware with Redis

### Tuning for Your Environment

**Low-memory servers (< 2GB RAM):**
```env
BROWSERSHOT_MEMORY_LIMIT=256
BROWSERSHOT_MAX_CONCURRENT_PDF=1
BROWSERSHOT_MAX_CONCURRENT_SCREENSHOTS=2
```

**High-memory servers (8GB+ RAM):**
```env
BROWSERSHOT_MEMORY_LIMIT=1024
BROWSERSHOT_MAX_CONCURRENT_PDF=5
BROWSERSHOT_MAX_CONCURRENT_SCREENSHOTS=10
```

**Heavy sites (lots of images/JS):**
```env
BROWSERSHOT_TIMEOUT=120
BROWSERSHOT_MEMORY_LIMIT=1024
```

## Screenshot Cleanup

Screenshots are automatically deleted to save disk space.

### Configuration

```env
AUDITS_DELETE_SCREENSHOTS_AFTER_PDF=true    # Delete after PDF generation
AUDITS_ORPHANED_SCREENSHOTS_RETENTION_HOURS=24  # Delete orphaned files after 24h
```

### How It Works

**Immediate Cleanup:**
- After PDF is successfully generated, screenshots are deleted
- Saves ~1MB per audit
- Configurable - set to `false` if you want to keep screenshots

**Orphan Cleanup:**
- Daily scheduled job: `php artisan audits:prune-orphaned-screenshots`
- Runs at 03:00 daily
- Removes screenshots older than 24 hours not associated with an audit
- Handles cases where PDF generation failed before cleanup

### Disk Space Savings

```
Without cleanup:
1000 audits/day × 1MB = 1GB/day = 30GB/month

With cleanup:
1000 audits/day × 0MB = 0GB/day = 0GB/month
```

## PageSpeed API Quota Management

Google PageSpeed API has strict rate limits. We track usage to prevent hitting limits.

### Configuration

```env
PAGESPEED_API_KEY=                          # Optional - increases quota
PAGESPEED_RATE_LIMIT_PER_MINUTE=6           # Free tier: 6 calls/minute
PAGESPEED_RATE_LIMIT_PER_DAY=25000          # With API key: 25,000 calls/day
```

### How It Works

**Quota Tracking:**
- Uses Redis cache to track calls per minute and per day
- Checks quota before making PageSpeed API call
- Throws exception if quota exceeded

**Automatic Retry:**
- If minute quota exceeded: job is released for 60 seconds
- If daily quota exceeded: job is released for 1 hour
- Jobs automatically retry when quota available

**Quota Alerts:**
- Logs warning when 80% of quota used
- Includes current usage percentage in logs

### API Key Benefits

**Without API key:**
- 6 calls per minute
- Shared across all users
- Frequent quota exceeded errors

**With API key:**
- 25,000 calls per day
- Dedicated quota
- Rare quota issues (unless you're doing 1000+ audits/hour)

## Queue Concurrency

Queue middleware prevents too many jobs from running simultaneously.

### Configuration

```env
QUEUE_PDF_CONCURRENCY=3                     # Max 3 PDFs being generated
QUEUE_SCREENSHOT_CONCURRENCY=5              # Max 5 screenshots being captured
QUEUE_MAX_DEPTH_ALERT=100                   # Alert if queue depth > 100
```

### How It Works

**Rate Limiting:**
- Uses Laravel's `RateLimited` middleware
- Registered in `AppServiceProvider`
- Jobs wait in queue if limit reached

**Overlap Prevention:**
- Uses `WithoutOverlapping` middleware
- Same audit can't be processed twice simultaneously
- Prevents race conditions

## Request Size Validation

Prevents DoS via large payloads.

### Configuration

```env
API_MAX_REQUEST_SIZE=1048576                # 1MB max request size
```

### How It Works

- Middleware checks `Content-Length` header
- Rejects requests > 1MB with 413 status code
- Returns size details in error message

### Response Example

```json
{
  "error": "Payload too large",
  "max_size": "1.00 MB",
  "received_size": "5.42 MB"
}
```

## Database Query Optimization

Composite indexes ensure queries are fast even with millions of audits.

### Indexes

```sql
-- Find pending/processing audits
INDEX (url, strategy, status)

-- Find recent failed audits
INDEX (status, created_at)
```

### Analyzing Queries

Run this command to verify indexes are being used:

```bash
php artisan audits:explain-queries
```

This runs `EXPLAIN` on common queries and shows if indexes are used.

## Performance Benchmarks

Expected performance on a 2GB RAM server with default settings:

| Operation | Time | Resource Usage |
|-----------|------|----------------|
| PageSpeed API fetch | 5-15s | 10MB RAM |
| Screenshot capture | 3-8s | 300MB RAM |
| PDF generation | 2-5s | 200MB RAM |
| **Total per audit** | **10-28s** | **500MB peak** |

**Concurrent capacity:**
- 3 PDFs + 5 screenshots = ~2GB RAM usage
- Handles ~10-15 concurrent audits safely
- Queue depth builds during traffic spikes

## Monitoring Resource Usage

Check current queue depth:

```bash
php artisan queue:work --once --verbose
```

Check failed jobs:

```bash
php artisan queue:failed
```

Check disk usage:

```bash
df -h storage/app/public/screenshots
df -h storage/app/public/reports
```

## Troubleshooting

### Queue workers dying

**Symptom:** Jobs stuck in `processing` state

**Solution:** Increase memory limit for queue workers
```bash
php artisan queue:work --memory=512 --timeout=180
```

### PageSpeed quota exceeded frequently

**Symptom:** Many audits failing with "quota exceeded"

**Solution 1:** Add API key to `.env`
```env
PAGESPEED_API_KEY=your-key-here
PAGESPEED_RATE_LIMIT_PER_DAY=25000
```

**Solution 2:** Lower concurrency to respect free tier limits
```env
QUEUE_PDF_CONCURRENCY=1
```

### Screenshots filling disk

**Symptom:** `storage/app/public/screenshots` growing indefinitely

**Solution:** Ensure cleanup is enabled and scheduled
```env
AUDITS_DELETE_SCREENSHOTS_AFTER_PDF=true
```

Check scheduler is running:
```bash
php artisan schedule:run
```

### Browsershot timeouts

**Symptom:** "Timeout exceeded" errors

**Solution:** Increase timeout for heavy sites
```env
BROWSERSHOT_TIMEOUT=120
```

Or reduce concurrent jobs:
```env
BROWSERSHOT_MAX_CONCURRENT_PDF=1
BROWSERSHOT_MAX_CONCURRENT_SCREENSHOTS=2
```

## Recommendations

### Production Settings (2GB server)

```env
# Conservative settings for stability
BROWSERSHOT_TIMEOUT=90
BROWSERSHOT_MEMORY_LIMIT=512
BROWSERSHOT_MAX_CONCURRENT_PDF=2
BROWSERSHOT_MAX_CONCURRENT_SCREENSHOTS=3
QUEUE_PDF_CONCURRENCY=2
QUEUE_SCREENSHOT_CONCURRENCY=3
PAGESPEED_RATE_LIMIT_PER_MINUTE=6
```

### Production Settings (8GB server)

```env
# Aggressive settings for throughput
BROWSERSHOT_TIMEOUT=60
BROWSERSHOT_MEMORY_LIMIT=1024
BROWSERSHOT_MAX_CONCURRENT_PDF=5
BROWSERSHOT_MAX_CONCURRENT_SCREENSHOTS=8
QUEUE_PDF_CONCURRENCY=5
QUEUE_SCREENSHOT_CONCURRENCY=8
PAGESPEED_API_KEY=your-key-here
PAGESPEED_RATE_LIMIT_PER_DAY=25000
```

### Development Settings

```env
# Relaxed settings for local testing
BROWSERSHOT_TIMEOUT=120
BROWSERSHOT_MEMORY_LIMIT=1024
BROWSERSHOT_MAX_CONCURRENT_PDF=1
BROWSERSHOT_MAX_CONCURRENT_SCREENSHOTS=1
AUDITS_DELETE_SCREENSHOTS_AFTER_PDF=false  # Keep for debugging
```

## See Also

- [Configuration Reference](configuration.md)
- [Monitoring Guide](monitoring.md)
- [Troubleshooting Guide](troubleshooting.md)
