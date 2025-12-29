# Troubleshooting Guide

This guide helps you debug common issues in the Rush CMS Audits microservice.

## Log Files

All logs are stored in `storage/logs/` and rotate daily:

- **audits.log** - Audit lifecycle, job execution, API requests (14 day retention)
- **webhooks.log** - Webhook delivery attempts and responses (7 day retention)
- **laravel.log** - General application logs (14 day retention)

## Reading Logs

Logs are structured in JSON format for easy parsing:

```bash
# Tail audit logs
tail -f storage/logs/audits-*.log

# Search by audit ID
grep "audit_id.*abc-123" storage/logs/audits-*.log

# Find errors only
grep '"level":"error"' storage/logs/audits-*.log

# Find failed jobs
grep '"job":.*"failed"' storage/logs/audits-*.log
```

## Common Issues

### 1. Audit Stuck in "pending" Status

**Symptoms:**
- Audit created but never progresses
- No jobs in queue

**Debug:**
```bash
# Check if queue worker is running
ps aux | grep "queue:work"

# Check queue depth
php artisan queue:work --once

# Check audit logs
grep "audit_id.*YOUR_AUDIT_ID" storage/logs/audits-*.log
```

**Common Causes:**
- Queue worker not running
- Job failed before logging (check `failed_jobs` table)

**Fix:**
```bash
# Start queue worker
php artisan queue:work --tries=3 --timeout=180

# Check failed jobs
php artisan queue:failed
```

---

### 2. Webhook Not Being Called

**Symptoms:**
- Audit completes but webhook never fires
- `webhook_delivered_at` is null in database

**Debug:**
```bash
# Check webhook logs
grep "audit_id.*YOUR_AUDIT_ID" storage/logs/webhooks-*.log

# Check audit record
php artisan tinker
>>> $audit = App\Models\Audit::find('YOUR_AUDIT_ID');
>>> $audit->webhook_attempts;
>>> $audit->webhook_status;
```

**Common Causes:**
1. `AUDITS_WEBHOOK_RETURN_URL` not configured
2. Webhook endpoint returning non-2xx status
3. Webhook endpoint timing out
4. Network connectivity issues

**Fix:**
```bash
# Test webhook URL manually
curl -X POST YOUR_WEBHOOK_URL \
  -H "Content-Type: application/json" \
  -d '{"test": true}'

# Check webhook configuration
grep AUDITS_WEBHOOK .env
```

---

### 3. PageSpeed Fetch Failing

**Symptoms:**
- Audit fails with "Failed to fetch PageSpeed data"
- `error_context` shows API errors

**Debug:**
```bash
# Check error context
php artisan tinker
>>> $audit = App\Models\Audit::find('YOUR_AUDIT_ID');
>>> $audit->error_context;

# Check PageSpeed API directly
curl "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=YOUR_URL&strategy=mobile"
```

**Common Causes:**
1. URL is not publicly accessible
2. PageSpeed API rate limit exceeded
3. URL blocked by SSRF protection
4. Network timeout

**Fix:**
- Verify URL is publicly accessible
- Add `PAGESPEED_API_KEY` to `.env` for higher rate limits
- Check `config/blocked-domains.php` for SSRF blocks
- Increase `timeout` in `FetchPageSpeedJob`

---

### 4. Screenshots Failing

**Symptoms:**
- Audit completes but `screenshots_data` is null
- Error: "Failed to capture screenshots"

**Debug:**
```bash
# Check if Chromium is installed
php artisan audit:check-browser

# Check screenshot logs
grep "screenshot" storage/logs/audits-*.log | grep "audit_id.*YOUR_AUDIT_ID"

# Test Browsershot manually
php artisan tinker
>>> use Spatie\Browsershot\Browsershot;
>>> Browsershot::url('https://example.com')->screenshot()->save('/tmp/test.png');
```

**Common Causes:**
1. Chromium not installed
2. Missing dependencies (`libgbm1`, `libnss3`, etc.)
3. Insufficient memory
4. URL requires authentication

**Fix:**
```bash
# Install Chromium (Debian/Ubuntu)
apt-get install chromium-browser

# Install dependencies
apt-get install -y libgbm1 libnss3 libxss1 libasound2

# Configure Browsershot paths in .env
BROWSERSHOT_CHROME_PATH=/usr/bin/google-chrome
BROWSERSHOT_NODE_BINARY=/usr/bin/node
BROWSERSHOT_NPM_BINARY=/usr/bin/npm
```

---

### 5. PDF Generation Failing

**Symptoms:**
- Audit fails at PDF generation step
- Error: "Failed to generate PDF"

**Debug:**
```bash
# Test PDF generation manually
php artisan test:pdf --lang=pt_BR

# Check disk space
df -h

# Check PDF logs
grep "PDF generation failed" storage/logs/audits-*.log
```

**Common Causes:**
1. Out of disk space
2. Chromium memory issues
3. Invalid template data
4. Missing fonts

**Fix:**
```bash
# Free disk space
php artisan audit:prune-pdfs

# Increase memory limit
echo "memory_limit = 512M" >> php.ini

# Test with minimal data
php artisan test:pdf
```

---

## Error Context Fields

When an audit fails, `error_context` contains debug information:

```json
{
  "exception": "App\\Exceptions\\PageSpeedFetchException",
  "file": "/app/app/Jobs/FetchPageSpeedJob.php",
  "line": 82,
  "url": "https://example.com",
  "strategy": "mobile",
  "duration_ms": 5432,
  "attempts": 3,
  "original_error": "Connection timeout"
}
```

**Key Fields:**
- `exception` - Exception class that was thrown
- `file` / `line` - Where the error occurred
- `duration_ms` - How long before failure
- `attempts` - Retry attempt number
- `original_error` - Root cause message

---

## Debugging Failed Jobs

```bash
# List failed jobs
php artisan queue:failed

# Inspect specific failed job
php artisan queue:failed | grep YOUR_AUDIT_ID

# Retry failed job
php artisan queue:retry JOB_ID

# Retry all failed jobs
php artisan queue:retry all

# Delete old failed jobs
php artisan audits:cleanup-failed-jobs
```

---

## Performance Issues

### Slow Audits

**Symptoms:**
- Audits taking > 2 minutes
- High `duration_ms` in logs

**Debug:**
```bash
# Check logs for slow operations
grep "duration_ms" storage/logs/audits-*.log | awk '$NF > 5000'

# Check queue depth
php artisan tinker
>>> Illuminate\Support\Facades\Queue::size();
```

**Common Causes:**
1. PageSpeed API slow
2. Screenshot capture timeout
3. Queue backlog
4. Insufficient resources

**Fix:**
- Increase worker count
- Add `PAGESPEED_API_KEY` for faster API
- Increase server resources

---

### High Memory Usage

**Symptoms:**
- PHP fatal error: "Allowed memory size exhausted"
- Server OOM killer

**Debug:**
```bash
# Monitor memory during audit
watch -n 1 free -h

# Check job memory usage
grep "memory" storage/logs/laravel-*.log
```

**Fix:**
```bash
# Increase PHP memory limit
echo "memory_limit = 512M" >> php.ini

# Limit concurrent jobs
QUEUE_CONNECTION=redis AUDITS_CONCURRENCY=1 php artisan queue:work
```

---

## Health Check

Use `/health` endpoint to verify system status:

```bash
curl http://localhost/health
```

**Response:**
```json
{
  "status": "healthy",
  "checks": {
    "database": "ok",
    "redis": "ok",
    "queue": "ok",
    "disk": "ok",
    "chromium": "ok"
  },
  "metrics": {
    "queue_depth": 5,
    "failed_jobs_last_hour": 0,
    "disk_usage_percent": 45,
    "audits_last_hour": 23
  }
}
```

**Status Codes:**
- `200` - All systems healthy
- `503` - One or more systems failing

**Check Values:**
- `ok` - Check passed
- `slow` - Check passed but slow (> 100ms database, > 50ms Redis)
- `warning` - Check passed with warnings (queue depth > 100, disk > 80%)
- `critical` - Check passed but critical (disk > 90%)
- `fail` - Check failed

---

## Getting Help

If you're still stuck:

1. Check logs with `grep` for your audit ID
2. Review `error_context` in database
3. Run `/health` to verify system status
4. Check `failed_jobs` table
5. Test components individually (PageSpeed API, Browsershot, PDF)

**Useful Commands:**
```bash
# Full debug trace for an audit
php artisan tinker
>>> $audit = App\Models\Audit::with('processingSteps')->find('YOUR_AUDIT_ID');
>>> $audit->processing_steps;
>>> $audit->error_context;

# Tail all logs simultaneously
tail -f storage/logs/*.log
```
