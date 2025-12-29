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

## Scheduler

The `audit:prune-pdfs` command runs daily automatically via Laravel Scheduler.

To enable, add to crontab:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```
