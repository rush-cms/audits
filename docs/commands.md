# Console Commands

## audit:create-token

Create an API token for a client.

```bash
php artisan audit:create-token {name}
```

**Arguments:**
- `name` - Client/integration name (e.g., "n8n", "main-app")

**Example:**
```bash
php artisan audit:create-token "n8n-production"
```

**Output:**
```
Token created successfully:

1|abc123def456...

Save this token now. It will not be shown again.
```

---

## audit:check-browser

Verify Chrome/Puppeteer configuration.

```bash
php artisan audit:check-browser
```

**Example output (success):**
```
Checking browser configuration...

Node: /usr/bin/node
NPM: /usr/bin/npm
Chrome: /usr/bin/chromium

✓ Browsershot is working correctly!
```

**Example output (failure):**
```
✗ Browsershot failed: Chrome not found at /usr/bin/google-chrome
```

---

## audit:prune-pdfs

Remove old PDF reports.

```bash
php artisan audit:prune-pdfs {--days=}
```

**Options:**
- `--days` - Override retention days (default from config)

**Examples:**
```bash
# Use default retention (7 days)
php artisan audit:prune-pdfs

# Keep only last 30 days
php artisan audit:prune-pdfs --days=30
```

**Output:**
```
Pruning PDFs older than 7 days...
Deleted: reports/old-audit-1.pdf
Deleted: reports/old-audit-2.pdf

Pruning complete. 2 file(s) deleted.
```

---

## audits:cleanup-failed-jobs

Remove old failed jobs from the dead letter queue.

```bash
php artisan audits:cleanup-failed-jobs
```

**Description:**
Deletes failed job records older than the configured retention period (default: 30 days). This prevents the `failed_jobs` table from growing indefinitely.

**Configuration:**
- `AUDITS_FAILED_JOBS_RETENTION_DAYS` - Days to keep failed jobs (default: 30)

**Example output:**
```
Deleted 5 failed job(s) older than 30 days.
```

**Recommendation:**
Run this command regularly (e.g., weekly) via cron or scheduler to maintain database hygiene.

---

## audits:prune-orphaned-screenshots

Remove orphaned screenshot files to save disk space.

```bash
php artisan audits:prune-orphaned-screenshots
```

**Description:**
Deletes screenshot files older than the configured retention period that are not associated with any audit. This handles cases where PDF generation failed before screenshots could be cleaned up.

**Configuration:**
- `AUDITS_ORPHANED_SCREENSHOTS_RETENTION_HOURS` - Hours before orphaned screenshots are deleted (default: 24)

**Example output:**
```
Deleted 12 orphaned screenshots (5.43 MB)
```

**How it works:**
1. Scans `storage/app/public/screenshots/`
2. Finds files older than retention period (default 24 hours)
3. Deletes them and reports total size freed

**Recommendation:**
This command runs daily at 03:00 via the scheduler. No manual intervention needed.

---

## audits:explain-queries

Analyze common database queries to verify index usage.

```bash
php artisan audits:explain-queries
```

**Description:**
Runs `EXPLAIN` on common queries to verify that database indexes are being used correctly. Useful for performance troubleshooting and ensuring query optimization.

**Example output:**
```
Analyzing common queries...

Query: Find pending/processing audits by URL and strategy
SELECT * FROM audits WHERE url = 'https://example.com' AND strategy = 'mobile' AND status IN ('pending', 'processing') ORDER BY created_at DESC LIMIT 1

+----+-------------+--------+-------+---------------------------+---------------------------+---------+------+------+-------------+
| id | select_type | table  | type  | possible_keys             | key                       | key_len | ref  | rows | Extra       |
+----+-------------+--------+-------+---------------------------+---------------------------+---------+------+------+-------------+
|  1 | SIMPLE      | audits | range | audits_url_strategy_index | audits_url_strategy_index | 1033    | NULL |    5 | Using where |
+----+-------------+--------+-------+---------------------------+---------------------------+---------+------+------+-------------+

Analysis complete!
```

**What to look for:**
- **type**: Should be `ref`, `range`, or `index` (not `ALL`)
- **key**: Should show an index name (not `NULL`)
- **rows**: Lower is better (ideally < 100)

**When to run:**
- After adding new indexes
- When investigating slow queries
- As part of performance optimization

---

## Scheduler

The following commands run automatically via Laravel Scheduler:

- `audit:prune-pdfs` - Daily (default time)
- `audits:prune-orphaned-screenshots` - Daily at 03:00

To enable, add to crontab:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

**Check scheduled tasks:**
```bash
php artisan schedule:list
```
