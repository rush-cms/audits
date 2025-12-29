# Sprint: Security and Validation

**Start Date:** 2025-12-29
**Completion Date:** 2025-12-29

## Goal

Eliminate critical security vulnerabilities and implement robust validation for all user inputs, protecting against SSRF, injection attacks, and ensuring only valid data enters the system.

## What was done

* [x] Task 1.1: Implement SafeUrl Value Object with SSRF protection
  * Created SafeUrl Value Object replacing old Url
  * Blocks private IPs (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, 169.254.0.0/16)
  * Blocks localhost (127.0.0.1, ::1, localhost)
  * Blocks file:// and other unsafe schemes
  * DNS resolution to prevent rebinding attacks
  * Configurable blocked domains via config/blocked-domains.php
  * SSRF protection only active in production (APP_ENV=production)
* [x] Task 1.2: Create Value Objects (Language, AuditStrategy) and enhance DTOs
  * Created Language Enum (en, pt_BR, es)
  * Created AuditStrategy Enum (mobile, desktop)
  * Created ScanData DTO using Spatie Data
  * Created custom casts (SafeUrlCast, LanguageCast, AuditStrategyCast)
  * Replaced manual validation in controller with typed DTOs
* [x] Task 1.3: Implement API Rate Limiting with Redis
  * Created ThrottleApiRequests middleware
  * Per-token limits: 60 req/min, 500 req/hour, 2000 req/day
  * Global limit: 200 req/min (all tokens)
  * X-RateLimit-* headers in all responses
  * 429 Too Many Requests with retry_after
  * Configurable via .env
* [x] Task 1.4: Add Webhook Signature Verification
  * Created WebhookSignature helper
  * HMAC-SHA256 signatures for webhook authenticity
  * Headers: X-Webhook-Signature, X-Webhook-Timestamp, X-Webhook-ID
  * 5-minute timestamp tolerance
  * Backward compatible (optional if secret not configured)
* [x] Update documentation (README, API docs, .env.example)
  * Added rate limiting examples and configuration
  * Added webhook signature verification examples (PHP and Node.js)
  * Documented SSRF protection behavior
  * Updated configuration reference table

## Issues Encountered

### Issue 1: PHPStan Generic Type Warnings

**Description:** Spatie Data's CreationContext generic types caused PHPStan warnings in custom casts.
**Solution:** Added PHPDoc annotations and generated baseline for legitimate warnings from third-party library.

### Issue 2: Environment Detection in Tests

**Description:** Unit tests failed because App facade wasn't bootstrapped, causing `isProduction()` check to fail.
**Solution:** Implemented custom `isProduction()` method using `getenv('APP_ENV')` instead of facade for better compatibility.

### Issue 3: Blocked Domains Configuration Approach

**Description:** Initial approach used .env comma-separated list which would be unwieldy for many domains.
**Solution:** Created dedicated `config/blocked-domains.php` file with simple array, removing logic from config and .env bloat.

### Issue 4: Rate Limit TTL Calculation

**Description:** Initial implementation tried to access Redis directly via Cache facade which caused type errors.
**Solution:** Simplified to use fixed 60-second retry calculation instead of dynamic TTL lookup.

## Wins

* 🎉 SSRF protection prevents AWS metadata leaks and internal network access
* 🎉 Rate limiting protects against API abuse and DDoS
* 🎉 Webhook signatures enable secure webhook verification
* 🎉 Type-safe DTOs with enums replace fragile manual validation
* 🎉 All tests passing (24 passed, 79 assertions)
* 🎉 PHPStan level 8 with zero errors (4 baseline warnings from Spatie)
* 🎉 Production-ready for moderate load (< 1000 audits/day)

## Metrics

| Metric | Value |
| --- | --- |
| Files created | 16 |
| Files modified | 10 |
| Files deleted | 1 (old Url.php) |
| Tests passing | 24 (79 assertions) |
| PHPStan errors | 0 |
| Pint issues | 0 |
| Commits | 5 |

## Commits

```bash
0582d4e refactor: simplify blocked domains with dedicated config file
925a914 fix: resolve PHPStan errors and update tests
8988d35 docs: update documentation for security and API features
bfede2e feat: implement rate limiting and webhook signatures
bb573f2 feat: add SSRF protection and improve validation with DTOs
```

## Next Steps

* [ ] Sprint 2: Reliability and Race Conditions
  * Fix race conditions in audit creation
  * State-based idempotency
  * Partial data persistence
  * Graceful degradation
