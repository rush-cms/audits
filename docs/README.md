# Rush CMS Audits - Documentation

Welcome to the Rush CMS Audits microservice documentation.

## Quick Start

1. [Installation](installation.md)
2. [Configuration](configuration.md)
3. [API Reference](api.md)
4. [Console Commands](commands.md)

## Operations

- [Performance & Resource Limits](performance.md)
- [Monitoring](monitoring.md)
- [Troubleshooting](troubleshooting.md)

## Overview

This microservice generates performance audit PDFs from Google PageSpeed Insights data. It's designed to be:

- **Headless** - No UI, API-only
- **Whitelabel** - Customizable branding
- **Async** - Queue-based processing
- **Webhook-oriented** - Callbacks on completion

## Architecture

```
Post /scan ──▶ AuditController ──▶ GenerateAuditJob ──▶ PdfGeneratorSvc ──▶ WebhookDispatcher
```

## License

MIT - See [LICENSE](../LICENSE) for details.
