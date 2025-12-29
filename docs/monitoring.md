# Monitoring Guide

This guide explains how to monitor the Rush CMS Audits microservice in production.

## Health Check Endpoint

### GET /health

Unauthenticated endpoint for monitoring system health.

**Response (200 OK):**
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

**Response (503 Service Unavailable):**
```json
{
  "status": "unhealthy",
  "checks": {
    "database": "ok",
    "redis": "fail",
    "queue": "warning",
    "disk": "ok",
    "chromium": "ok"
  },
  "metrics": {
    "queue_depth": 150,
    "failed_jobs_last_hour": 12,
    "disk_usage_percent": 45,
    "audits_last_hour": 0
  }
}
```

---

## Check Statuses

### Database
- **ok** - Connection successful, query < 100ms
- **slow** - Connection successful, query > 100ms
- **fail** - Connection failed or query error

### Redis
- **ok** - Set/get successful, operation < 50ms
- **slow** - Set/get successful, operation > 50ms
- **fail** - Connection failed or operation error

### Queue
- **ok** - Queue depth < 100, failed jobs < 10/hour
- **warning** - Queue depth > 100 OR failed jobs > 10/hour
- **fail** - Cannot access queue

### Disk
- **ok** - Disk usage < 80%
- **warning** - Disk usage 80-89%
- **critical** - Disk usage > 90%
- **fail** - Cannot check disk usage

### Chromium
- **ok** - Binary exists and is executable
- **fail** - Binary missing or not executable

---

## Metrics

### queue_depth
Number of jobs waiting in queue.

**Thresholds:**
- `< 50` - Normal
- `50-100` - Busy
- `> 100` - Warning (triggers queue check warning)

**Actions:**
- If consistently > 100, increase worker count
- If rapidly increasing, investigate job failures

### failed_jobs_last_hour
Failed jobs in the last hour.

**Thresholds:**
- `0-5` - Normal
- `6-10` - Monitor
- `> 10` - Warning (triggers queue check warning)

**Actions:**
- Check `failed_jobs` table for patterns
- Review logs for error context
- Retry if transient errors

### disk_usage_percent
Percentage of disk space used.

**Thresholds:**
- `< 80%` - Normal
- `80-89%` - Warning
- `> 90%` - Critical

**Actions:**
- Run `php artisan audit:prune-pdfs` to cleanup old PDFs
- Increase disk space
- Reduce retention period in config

### audits_last_hour
Audits created in the last hour.

**Thresholds:**
- Varies by usage
- `0` may indicate service not being used or API issues
- Sudden drop may indicate upstream problems

**Actions:**
- Verify API tokens are working
- Check for rate limiting
- Review upstream service health

---

## Log Files

### audits/
Audit lifecycle events, job execution, API requests.

**Location:** `storage/logs/audits/app-YYYY-MM-DD.log`
**Retention:** 30 days
**Rotation:** Daily

**Key Events:**
- `API request received` - New scan request
- `New audit created` - Audit record created
- `PageSpeed data fetched` - PageSpeed API success
- `Screenshots captured` - Screenshot success
- `Audit completed` - Full pipeline success
- `PageSpeed fetch failed` - PageSpeed job error
- `Job permanently failed` - Job exhausted retries

**Example:**
```json
{
  "level": "info",
  "message": "Audit completed",
  "context": {
    "audit_id": "abc-123",
    "score": 95,
    "pdf_size": 245678,
    "duration_ms": 45320
  },
  "time": "2025-12-29 12:34:56"
}
```

### webhooks/
Webhook delivery attempts, responses, failures.

**Location:** `storage/logs/webhooks/app-YYYY-MM-DD.log`
**Retention:** 14 days
**Rotation:** Daily

**Key Events:**
- `Dispatching webhook` - Webhook attempt started
- `Webhook delivered successfully` - 2xx response
- `Webhook returned non-2xx status` - 4xx/5xx response
- `Webhook delivery failed` - Connection error

**Example:**
```json
{
  "level": "info",
  "message": "Webhook delivered successfully",
  "context": {
    "audit_id": "abc-123",
    "status": 200,
    "duration_ms": 123,
    "response_body": "{\"success\": true}"
  },
  "time": "2025-12-29 12:35:45"
}
```

---

## Alerting Rules

### Critical Alerts (Page Immediately)

1. **Service Down**
   - Condition: `/health` returns 503 for > 5 minutes
   - Action: Check all health checks, review logs

2. **Disk Critical**
   - Condition: `disk_usage_percent` > 90%
   - Action: Prune PDFs, add disk space

3. **Database Failure**
   - Condition: `checks.database` = "fail" for > 2 minutes
   - Action: Check database connectivity, review DB logs

4. **Queue Worker Down**
   - Condition: No job processing for > 10 minutes
   - Action: Restart queue worker

### Warning Alerts (Investigate)

1. **High Queue Depth**
   - Condition: `queue_depth` > 100 for > 15 minutes
   - Action: Increase workers or investigate slow jobs

2. **High Failed Jobs**
   - Condition: `failed_jobs_last_hour` > 10
   - Action: Review failed job patterns

3. **Slow Database**
   - Condition: `checks.database` = "slow" for > 5 minutes
   - Action: Check database load, review slow queries

4. **Disk Warning**
   - Condition: `disk_usage_percent` > 80%
   - Action: Schedule cleanup, plan capacity increase

5. **Webhook Failures**
   - Condition: > 5 webhook failures in 1 hour
   - Action: Verify webhook endpoint health

---

## Monitoring Setup

### Uptime Monitoring

Monitor `/health` endpoint every 1 minute:

```bash
# curl
curl -f http://your-domain.com/health || echo "Health check failed"

# Uptime Kuma
GET http://your-domain.com/health
Expected: 200
Interval: 60s
```

### Log Aggregation

Use Graylog, Datadog, or ELK stack to aggregate logs:

```bash
# Ship logs to aggregator
tail -F storage/logs/*.log | your-log-shipper

# Filter by audit_id
grep "audit_id.*abc-123"

# Alert on errors
grep '"level":"error"' | wc -l
```

### Metrics Collection

Scrape `/health` metrics periodically:

```python
import requests
import time

while True:
    response = requests.get('http://your-domain.com/health')
    data = response.json()

    # Send to monitoring system
    send_metric('queue_depth', data['metrics']['queue_depth'])
    send_metric('failed_jobs', data['metrics']['failed_jobs_last_hour'])
    send_metric('disk_usage', data['metrics']['disk_usage_percent'])
    send_metric('audits_created', data['metrics']['audits_last_hour'])

    time.sleep(60)
```

---

## Performance Metrics

### Audit Processing Time

Monitor `duration_ms` in logs:

```bash
# Average duration for completed audits (last hour)
grep "Audit completed" storage/logs/audits-*.log | \
  jq -r '.context.duration_ms' | \
  awk '{sum+=$1; count++} END {print sum/count}'

# P95 duration
grep "Audit completed" storage/logs/audits-*.log | \
  jq -r '.context.duration_ms' | \
  sort -n | \
  awk 'BEGIN{c=0} {a[c++]=$1} END{print a[int(c*0.95)]}'
```

**Typical Ranges:**
- PageSpeed fetch: 2-10 seconds
- Screenshot capture: 5-15 seconds
- PDF generation: 3-8 seconds
- Total: 15-60 seconds

### Webhook Delivery

Monitor webhook success rate:

```bash
# Success rate (last hour)
total=$(grep "Dispatching webhook" storage/logs/webhooks-*.log | wc -l)
success=$(grep "Webhook delivered successfully" storage/logs/webhooks-*.log | wc -l)
echo "scale=2; $success / $total * 100" | bc
```

**Target:** > 95% success rate

---

## Database Queries

### Current System State

```sql
-- Active audits by status
SELECT status, COUNT(*)
FROM audits
WHERE created_at >= NOW() - INTERVAL 1 HOUR
GROUP BY status;

-- Failed audits with errors
SELECT id, url, error_message, created_at
FROM audits
WHERE status = 'failed'
AND created_at >= NOW() - INTERVAL 1 DAY
ORDER BY created_at DESC;

-- Webhook delivery stats
SELECT
  COUNT(*) as total,
  SUM(CASE WHEN webhook_status >= 200 AND webhook_status < 300 THEN 1 ELSE 0 END) as success,
  AVG(webhook_attempts) as avg_attempts
FROM audits
WHERE webhook_delivered_at IS NOT NULL
AND created_at >= NOW() - INTERVAL 1 HOUR;

-- Top failed domains
SELECT
  SUBSTRING_INDEX(SUBSTRING_INDEX(url, '/', 3), '://', -1) as domain,
  COUNT(*) as failures
FROM audits
WHERE status = 'failed'
AND created_at >= NOW() - INTERVAL 1 DAY
GROUP BY domain
ORDER BY failures DESC
LIMIT 10;
```

---

## Automated Maintenance

### Daily Cleanup (Cron)

```cron
# Prune old PDFs daily at 2 AM
0 2 * * * cd /app && php artisan audit:prune-pdfs >> /var/log/audit-prune.log 2>&1

# Cleanup old failed jobs weekly on Sunday at 3 AM
0 3 * * 0 cd /app && php artisan audits:cleanup-failed-jobs >> /var/log/failed-jobs-cleanup.log 2>&1

# Health check every minute
* * * * * curl -f http://localhost/health || echo "Health check failed" | mail -s "Audits Service Down" admin@example.com
```

### Log Rotation

Logs rotate daily by default. Configure retention in `config/logging.php`:

```php
'audits' => [
    'driver' => 'daily',
    'path' => storage_path('logs/audits.log'),
    'level' => 'info',
    'days' => 14, // Keep 14 days
],

'webhooks' => [
    'driver' => 'daily',
    'path' => storage_path('logs/webhooks.log'),
    'level' => 'info',
    'days' => 7, // Keep 7 days
],
```

---

## Incident Response

### Service Degraded

1. Check `/health` for failed checks
2. Review recent logs (`tail -f storage/logs/*.log`)
3. Check queue worker status (`ps aux | grep queue:work`)
4. Verify external dependencies (PageSpeed API, database, Redis)
5. Check resource usage (`htop`, `df -h`)

### High Error Rate

1. Query `failed_jobs` table for patterns
2. Review `error_context` in audits table
3. Check for common error messages in logs
4. Verify external service availability
5. Check for recent deployments

### Webhook Failures

1. Test webhook endpoint manually: `curl -X POST YOUR_WEBHOOK_URL`
2. Check webhook logs for status codes
3. Verify webhook signature validation
4. Check client-side logs/monitoring
5. Review network connectivity

---

## SLA Targets

**Availability:**
- `/health` endpoint: 99.9% uptime
- API endpoints: 99.5% uptime

**Performance:**
- Audit completion: < 2 minutes (p95)
- Webhook delivery: < 5 seconds (p95)
- Health check response: < 2 seconds

**Reliability:**
- Job success rate: > 95%
- Webhook delivery rate: > 95%
- Failed job retry success: > 80%

---

## Dashboard Metrics

Recommended metrics for monitoring dashboard:

1. **Availability**
   - Health check status (green/red)
   - Uptime percentage (last 24h)

2. **Throughput**
   - Audits created (per hour)
   - Audits completed (per hour)
   - Queue depth (current)

3. **Latency**
   - Average audit duration
   - P95 audit duration
   - Queue wait time

4. **Errors**
   - Failed jobs (last hour)
   - Failed audits (last hour)
   - Webhook failures (last hour)

5. **Resources**
   - Disk usage (%)
   - CPU usage (%)
   - Memory usage (%)
   - Queue worker count
