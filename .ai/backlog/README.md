# Product Backlog

This directory contains the product backlog for Rush CMS Site Audits, organized into prioritized sprints and future feature ideas.

---

## 📋 Sprint Organization

Sprints are ordered by priority and should be implemented in sequence:

### 🔴 Critical Priority

1. **[Sprint 1: Security and Validation](sprint-1-security-validation.md)** (3-5 days)
   - SSRF protection
   - SafeUrl Value Object
   - Rate limiting
   - Webhook signatures

2. **[Sprint 2: Reliability and Race Conditions](sprint-2-reliability-race-conditions.md)** (4-6 days)
   - Fix race conditions in audit creation
   - State-based idempotency
   - Partial data persistence
   - Graceful degradation

3. **[Sprint 3: Observability and Logging](sprint-3-observability-logging.md)** (3-4 days)
   - Structured logging throughout codebase
   - Error tracking with context
   - Health check endpoints
   - Webhook observability

### 🟠 High Priority

4. **[Sprint 4: Performance and Resource Limits](sprint-4-performance-limits.md)** (3-4 days)
   - Browsershot resource limits
   - Screenshot cleanup
   - Queue concurrency limits
   - PageSpeed API quota tracking

5. **[Sprint 5: Webhook Reliability](sprint-5-webhook-reliability.md)** (2-3 days)
   - Response validation
   - Improved retry strategy
   - Webhook delivery history
   - Fallback notifications

### 🟢 Nice-to-Have

6. **[Future Features](backlog-nice-to-have.md)** (Post-production)
   - S3 storage support
   - Admin dashboard
   - Scheduled audits
   - Multi-tenant support
   - And many more...

---

## 🎯 Current State Assessment

**Project Score:** 68/100 (honest assessment)

**Major Gaps:**
- Security vulnerabilities (SSRF, no rate limiting)
- Race conditions in concurrent requests
- Zero logging/observability
- No resource limits (memory bombs possible)
- Silent webhook failures

**Completing Critical + High Priority sprints will bring score to 90+/100.**

---

## 📊 Total Effort Estimation

| Priority | Sprints | Days | Hours |
|----------|---------|------|-------|
| 🔴 Critical | 1-3 | 10-15 | 73-103 |
| 🟠 High | 4-5 | 5-7 | 47-66 |
| **Total** | **5** | **15-22** | **120-169** |

**Realistic Timeline:** 3-4 weeks for one developer working full-time

---

## 🏃 How to Use This Backlog

### 1. Start with Critical Priority

**Do NOT start development until Sprint 1-3 are complete.**

These address security vulnerabilities and reliability issues that will cause production incidents.

### 2. Each Sprint is Self-Contained

Each sprint document includes:
- Detailed problem context
- Task breakdown with implementation guidance
- Acceptance criteria
- Test requirements
- Documentation updates needed
- Effort estimation

### 3. Track Progress

Use GitHub Issues/Projects to track implementation:

```
Sprint 1: Security and Validation
├─ Issue: Implement SafeUrl Value Object
├─ Issue: Add Rate Limiting
├─ Issue: Implement Webhook Signatures
└─ Issue: Update Documentation
```

### 4. Definition of Done

Each sprint has explicit "Definition of Done" checklist. **All items must be checked before moving to next sprint.**

Typical requirements:
- [ ] All tasks implemented
- [ ] Tests passing (unit + integration)
- [ ] PHPStan level 8 passing
- [ ] Pint formatting correct
- [ ] Documentation updated
- [ ] Code review completed

### 5. Don't Skip Tests

Each sprint includes specific test requirements. **Skipping tests will cause problems in production.**

Write tests as you implement features, not after.

### 6. Update Documentation

Each sprint includes documentation updates. Keep docs in sync with code.

Documentation debt compounds quickly and becomes hard to pay back later.

---

## 🚨 Common Mistakes to Avoid

### ❌ Don't Implement Out of Order

Sprint order is deliberate. Later sprints assume earlier ones are complete.

Example: Sprint 3 (Observability) logs webhook failures. But if you haven't implemented Sprint 5 (Webhook Reliability) response validation, you'll log garbage data.

### ❌ Don't Cherry-Pick Tasks

Each sprint is cohesive. Don't implement half of Sprint 1 and half of Sprint 2.

Finish entire sprints before moving on.

### ❌ Don't Skip Critical Sprints

You might think "I'll add logging later". You won't.

Production incidents will happen, and you'll have no way to debug them.

Implement observability BEFORE you need it.

### ❌ Don't Implement Nice-to-Have First

"I'll add S3 support first, then do security" = recipe for disaster.

Nice-to-have features don't matter if the service crashes or gets hacked.

### ❌ Don't Skip Documentation

Future you (and your users) will curse past you.

Document as you go, not "later".

---

## 📈 Expected Outcomes

### After Sprint 1-3 (Critical)

- ✅ No security vulnerabilities
- ✅ No race conditions
- ✅ Full observability
- ✅ Debuggable in production
- ✅ Professional error handling

**Service is production-ready for moderate load (< 1000 audits/day).**

### After Sprint 4-5 (High)

- ✅ Resource-efficient
- ✅ Handles traffic spikes
- ✅ Reliable webhooks
- ✅ Observable webhook delivery
- ✅ Resilient to failures

**Service is production-ready for high load (10,000+ audits/day).**

### After Nice-to-Have Features

- ✅ Enterprise features
- ✅ Revenue opportunities
- ✅ Competitive differentiation
- ✅ Better UX
- ✅ Easier operations

**Service is market-competitive and scalable.**

---

## 🎓 Learning from Each Sprint

### Sprint 1: Security is Not Optional

You'll learn about SSRF attacks, rate limiting strategies, and why input validation matters.

### Sprint 2: Concurrency is Hard

You'll experience race conditions firsthand and learn why optimistic locking exists.

### Sprint 3: You Can't Fix What You Can't See

You'll realize how impossible production debugging is without logs.

### Sprint 4: Resource Limits Save Lives

You'll learn why unbounded processes are dangerous and how to protect your server.

### Sprint 5: Webhooks are Surprisingly Complex

You'll discover all the ways webhooks can fail and how to make them reliable.

---

## 🔄 Continuous Improvement

After completing all sprints:

1. **Monitor Production Metrics**
   - What's actually breaking?
   - What's slow?
   - What do users complain about?

2. **Revisit Nice-to-Have**
   - Which features would solve real problems?
   - Which would drive revenue?
   - Which would reduce support burden?

3. **Keep Backlog Updated**
   - Add new ideas
   - Reprioritize based on learnings
   - Remove ideas that no longer make sense

---

## 📞 Questions?

If you're unsure about:
- **Implementation details:** Read the full sprint document
- **Priority:** Follow the sprint order as listed
- **Effort estimates:** These are realistic for an experienced developer
- **Whether to skip something:** Don't. Everything is there for a reason.

---

## 🎯 Final Note

**This backlog represents the difference between:**
- A portfolio project vs. production-ready service
- 68/100 vs. 95+/100
- "Works on my machine" vs. "Works at scale"
- Amateur vs. professional

**The gap is 15-22 days of focused work.**

Worth it? Absolutely.

The difference between good code and production-ready code is:
- Security
- Reliability
- Observability
- Performance
- Error handling

All of which are covered in these sprints.

Good luck! 🚀
