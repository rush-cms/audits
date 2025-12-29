# Sprint 1: Security and Validation (CRITICAL)

**Estimated Duration:** 3-5 days
**Priority:** 🔴 CRITICAL
**Risk:** High - active security vulnerabilities

---

## Objective

Eliminate critical security vulnerabilities and implement robust validation for all user inputs, protecting against SSRF, injection attacks, and ensuring only valid data enters the system.

---

## Problem Context

### 1. SSRF (Server-Side Request Forgery) Vulnerability

**Current Problem:**
The `/api/v1/scan` endpoint accepts any URL without private network validation. An attacker can provide internal URLs like:
- `http://169.254.169.254/latest/meta-data/` (AWS metadata endpoint)
- `http://localhost:8000/admin` (internal services)
- `http://192.168.1.1/` (local network)

**Impact:**
- AWS/GCP credentials leakage
- Internal API access
- Internal network port scanning
- File reading via `file://` scheme

**Attack Scenario:**
```
POST /api/v1/scan
{
  "url": "http://169.254.169.254/latest/meta-data/iam/security-credentials/ec2-role"
}
```

Result: PageSpeed service makes request to AWS metadata, returns credentials in PDF or error message.

### 2. Primitive Manual Validation

**Current Problem:**
Validation happens directly in controller with `if` statements, violating Laravel Boost guidelines and creating fragile code.

**Issues:**
- Duplicated validation across methods
- Inconsistent error messages
- Difficult to add new rules
- No array shape validation
- No localized custom messages

### 3. Missing Rate Limiting

**Current Problem:**
API has no rate limiting. A single token can make:
- 1000 requests/second
- Exhaust PageSpeed API quota
- Consume all disk space
- Bring down workers with overload

**Cost Impact:**
- 10,000 audits × 2MB PDF = 20GB storage
- PageSpeed API quota exceeded = service stops
- Overloaded workers = timeouts for everyone

---

## Tasks

### Task 1.1: Implement URL Whitelist and SSRF Protection

**Objective:** Validate that submitted URLs are public and safe.

**Implementation:**

1. **Create `SafeUrl` Value Object**
   - Replace current `Url` with `SafeUrl`
   - Validate scheme (only `http` and `https`)
   - Validate host is not private/reserved IP
   - Validate host is not localhost
   - Validate against configurable blacklist

2. **Ranges to Block:**
   - `127.0.0.0/8` - Localhost
   - `10.0.0.0/8` - RFC1918 private
   - `172.16.0.0/12` - RFC1918 private
   - `192.168.0.0/16` - RFC1918 private
   - `169.254.0.0/16` - Link-local
   - `::1` - IPv6 localhost
   - `fc00::/7` - IPv6 private

3. **Configurable Domain Blacklist:**
   - Add config `audits.blocked_domains`
   - Allow blocking specific domains (eg: `internal.company.com`)

4. **DNS Resolution:**
   - Perform DNS lookup of hostname
   - Validate resolved IPs are not in private ranges
   - Prevent DNS rebinding attacks

**Acceptance Criteria:**
- [ ] `SafeUrl::from('http://169.254.169.254')` throws exception
- [ ] `SafeUrl::from('http://localhost')` throws exception
- [ ] `SafeUrl::from('http://192.168.1.1')` throws exception
- [ ] `SafeUrl::from('file:///etc/passwd')` throws exception
- [ ] `SafeUrl::from('https://example.com')` passes
- [ ] Blocked domains in config are rejected
- [ ] DNS rebinding is prevented

---

### Task 1.2: Create Form Requests for Validation

**Objective:** Move all validation to Form Request classes, following Laravel best practices.

**Implementation:**

1. **Create `ScanRequest`**
   - Validate `url` (required, string, using SafeUrl)
   - Validate `lang` (in:en,pt_BR,es)
   - Validate `strategy` (in:mobile,desktop)
   - Localized custom error messages
   - Authorization logic (check token scopes in future)

2. **Descriptive Error Messages:**
   ```
   url.required: The URL field is required
   url.invalid: The provided URL is not valid
   url.private: Private network URLs are not allowed
   url.blocked: This domain is blocked
   ```

3. **Refactor AuditController:**
   - Remove manual validation
   - Inject `ScanRequest` in `store()` method
   - Use `$request->validated()` for clean data

**Acceptance Criteria:**
- [ ] `ScanRequest` exists and validates all fields
- [ ] AuditController has no manual validation
- [ ] Error messages are clear and localized
- [ ] Tests pass with Form Request
- [ ] PHPStan level 8 passes

---

### Task 1.3: Implement API Rate Limiting

**Objective:** Prevent API abuse and protect system resources.

**Implementation:**

1. **Rate Limit per Token:**
   - 60 requests/minute per token (default)
   - 500 requests/hour per token
   - 2000 requests/day per token
   - Configurable via `.env`

2. **Global Rate Limit:**
   - 200 requests/minute globally (all tokens)
   - Prevents DDoS even with multiple tokens

3. **Rate Limit Headers:**
   ```
   X-RateLimit-Limit: 60
   X-RateLimit-Remaining: 45
   X-RateLimit-Reset: 1640000000
   ```

4. **429 Too Many Requests Response:**
   ```json
   {
     "message": "Too many requests. Please try again in 32 seconds.",
     "retry_after": 32
   }
   ```

5. **Configuration:**
   ```env
   AUDITS_RATE_LIMIT_PER_MINUTE=60
   AUDITS_RATE_LIMIT_PER_HOUR=500
   AUDITS_RATE_LIMIT_PER_DAY=2000
   ```

6. **Throttle Middleware:**
   - Apply in `routes/api.php`
   - Use Redis for counters (faster than DB)
   - Implement sliding window algorithm

**Acceptance Criteria:**
- [ ] 61st request in 1 minute returns 429
- [ ] Rate limit headers are present
- [ ] `retry_after` is calculated correctly
- [ ] Rate limits are configurable via .env
- [ ] Redis cache is used for counters
- [ ] Rate limiting tests pass

---

### Task 1.4: Add Webhook Signature Verification

**Objective:** Allow webhook receivers to validate authenticity.

**Implementation:**

1. **Generate HMAC Signature:**
   - Use configurable secret: `AUDITS_WEBHOOK_SECRET`
   - Algorithm: `hash_hmac('sha256', $payload, $secret)`
   - Include timestamp to prevent replay attacks

2. **Webhook Headers:**
   ```
   X-Webhook-Signature: sha256=abc123...
   X-Webhook-Timestamp: 1640000000
   X-Webhook-ID: unique-delivery-id
   ```

3. **Document Verification:**
   - Add example in docs/api.md
   - Show how to verify in PHP, Node, Python
   - Explain timestamp window (5 minutes)

4. **Optional Configuration:**
   - If `AUDITS_WEBHOOK_SECRET` not configured, don't send signature
   - Backward compatible with existing clients

**Acceptance Criteria:**
- [ ] Webhook includes signature when secret configured
- [ ] Signature can be verified by receiver
- [ ] Timestamp prevents replay attacks
- [ ] Documentation includes verification examples
- [ ] Backward compatible (secret optional)

---

## Risks and Dependencies

**Risks:**
- **SafeUrl may block legitimate URLs:** Implement configurable whitelist for special cases
- **Rate limiting too aggressive:** Start conservative, adjust based on metrics
- **DNS lookup adds latency:** Implement DNS lookup caching

**Dependencies:**
- Redis for rate limiting (already project dependency)
- No breaking API changes (backward compatible)

**Migration:**
- Form Requests are drop-in replacement (no breaking changes)
- Rate limiting starts permissive, can tighten later
- Webhook signature is optional (backward compatible)

---

## Required Tests

### Unit Tests:
- [ ] SafeUrl validates private IPs correctly
- [ ] SafeUrl validates schemes correctly
- [ ] SafeUrl validates blocked domains
- [ ] ScanRequest validates all fields
- [ ] ScanRequest returns correct error messages

### Integration Tests:
- [ ] Request with private IP returns 422
- [ ] Request with valid URL passes
- [ ] Rate limit is applied correctly
- [ ] 429 returns correct headers
- [ ] Webhook signature is verifiable

### Security Tests:
- [ ] SSRF via DNS rebinding is prevented
- [ ] File:// scheme is blocked
- [ ] localhost is blocked
- [ ] 169.254.169.254 is blocked

---

## Definition of Done

- [ ] All tasks implemented
- [ ] Unit tests passing
- [ ] Integration tests passing
- [ ] PHPStan level 8 no errors
- [ ] Pint formatting correct
- [ ] API documentation updated
- [ ] CHANGELOG.md updated
- [ ] Security review passed
- [ ] Code review approved

---

## Documentation to Update

1. **docs/api.md:**
   - Add security section
   - Document rate limits
   - Document webhook signatures
   - 429 error examples

2. **docs/configuration.md:**
   - Document `AUDITS_WEBHOOK_SECRET`
   - Document `AUDITS_RATE_LIMIT_*`
   - Document `audits.blocked_domains`

3. **README.md:**
   - Add security badge
   - Mention SSRF protections
   - Mention rate limiting

4. **SECURITY.md:**
   - Create security policy
   - Vulnerability report process
   - Responsible disclosure

---

## Effort Estimation

| Task | Estimate | Priority |
|------|----------|----------|
| 1.1 SafeUrl + SSRF | 6-8h | P0 |
| 1.2 Form Requests | 3-4h | P0 |
| 1.3 Rate Limiting | 4-6h | P0 |
| 1.4 Webhook Signature | 2-3h | P1 |
| Testing | 6-8h | P0 |
| Documentation | 2-3h | P1 |

**Total:** 23-32 hours (3-5 days)
