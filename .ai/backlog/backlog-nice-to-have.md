# Nice-to-Have Features (Future Backlog)

**Priority:** 🟢 LOW
**Timeline:** Post-production stabilization

These features would enhance the project but are not critical for production. Implement after critical and high priority sprints are complete.

---

## S3 / Cloud Storage Support

**Problem:** Currently only supports local storage, doesn't scale well.

**Solution:**
- Support S3, Cloudflare R2, DigitalOcean Spaces
- Use Laravel's filesystem abstraction
- Configure via `FILESYSTEM_DISK=s3`
- Signed URLs for private access
- Automatic CDN integration

**Benefit:**
- Unlimited storage
- Better performance with CDN
- Geographic distribution
- Professional production setup

**Effort:** 6-8 hours

---

## CORS Configuration

**Problem:** No CORS headers configured, frontend apps will fail.

**Solution:**
- Add `fruitcake/laravel-cors` package
- Configure allowed origins
- Support preflight requests
- Configurable via .env

**Benefit:**
- Frontend SPAs can consume API
- Better developer experience
- More integration options

**Effort:** 2-3 hours

---

## Audit Factory for Testing

**Problem:** Tests manually create audits with magic strings.

**Solution:**
- Create `AuditFactory` with realistic data
- Support different states (pending, processing, completed, failed)
- Support with/without screenshots
- Support different strategies and languages

**Benefit:**
- Cleaner tests
- Faster test writing
- Consistent test data
- Better test coverage

**Effort:** 3-4 hours

---

## Integration Tests with Real Queue

**Problem:** All tests use `Queue::fake()`, don't test actual job execution.

**Solution:**
- Create separate test suite for integration tests
- Use `RefreshDatabase` + actual queue
- Test full audit lifecycle end-to-end
- Mock only external APIs (PageSpeed, Browsershot)

**Benefit:**
- Catch serialization bugs
- Catch job chaining bugs
- More confidence in production behavior
- Real coverage metrics

**Effort:** 8-12 hours

---

## Saga Pattern for Job Pipeline

**Problem:** Job failures don't compensate (rollback) previous steps.

**Solution:**
- Implement saga pattern
- Each job has compensation logic
- If screenshot fails, still save PageSpeed data
- If PDF fails, still mark audit as partial success
- Allow manual resume from last successful step

**Benefit:**
- Better failure recovery
- Don't waste API quota
- Better UX for retries
- More resilient system

**Effort:** 12-16 hours

---

## GraphQL API

**Problem:** REST API requires multiple requests for related data.

**Solution:**
- Add GraphQL endpoint alongside REST
- Single query can fetch audit + deliveries + metadata
- Better for complex client requirements
- Self-documenting via introspection

**Benefit:**
- Better developer experience
- Fewer round trips
- More flexible queries
- Modern API architecture

**Effort:** 10-14 hours

---

## Multi-Tenant Support

**Problem:** All users share same namespace, can't separate clients.

**Solution:**
- Add `tenant_id` to audits
- Scope queries by tenant
- Separate rate limits per tenant
- Separate storage paths
- Tenant-specific branding

**Benefit:**
- True white-label capability
- Reseller friendly
- Better access control
- Revenue opportunities

**Effort:** 16-20 hours

---

## Admin Dashboard

**Problem:** No UI to monitor system health or manage audits.

**Solution:**
- Laravel Nova or Filament admin panel
- View all audits
- Manually retry failed audits
- View webhook delivery history
- System health overview
- User management

**Benefit:**
- Better operations
- Non-technical admin access
- Faster troubleshooting
- Professional appearance

**Effort:** 20-24 hours

---

## Metrics and APM Integration

**Problem:** No production metrics or tracing.

**Solution:**
- Integrate New Relic, DataDog, or Prometheus
- Custom metrics for business logic
- Distributed tracing across jobs
- Real-time alerting
- Performance dashboards

**Benefit:**
- Proactive issue detection
- Performance optimization
- Capacity planning
- Professional monitoring

**Effort:** 8-12 hours

---

## PDF Template Customization

**Problem:** PDF design is hardcoded, no client customization.

**Solution:**
- Template system with variables
- Upload custom logo per tenant
- Customize colors, fonts, layout
- Preview before generation
- Template versioning

**Benefit:**
- True white-label
- Client branding
- Marketing differentiation
- Revenue opportunity

**Effort:** 24-32 hours

---

## Scheduled Audits

**Problem:** Can't automatically re-audit sites periodically.

**Solution:**
- Add `scheduled_audits` table
- Cron schedule per URL
- Daily, weekly, monthly options
- Email digest of changes
- Trend charts

**Benefit:**
- Continuous monitoring
- Detect regressions
- Subscription revenue model
- Better client value

**Effort:** 12-16 hours

---

## Audit Comparisons

**Problem:** Can't compare current audit with previous.

**Solution:**
- Store audit history per URL
- Compare scores over time
- Highlight improvements/regressions
- Visual diff in PDF
- Trend graphs

**Benefit:**
- Show ROI of optimizations
- Track progress
- Better reporting
- Client retention

**Effort:** 10-14 hours

---

## Lighthouse Plugin System

**Problem:** Can only run standard Lighthouse audits.

**Solution:**
- Support custom Lighthouse plugins
- Upload custom audit configs
- Run custom checks
- Merge results with standard report

**Benefit:**
- Industry-specific audits
- Company-specific standards
- Competitive differentiation
- Advanced use cases

**Effort:** 16-20 hours

---

## Batch Audits

**Problem:** Can't audit multiple URLs at once efficiently.

**Solution:**
- Accept array of URLs in single request
- Process in parallel (with limits)
- Single combined report or separate PDFs
- Batch webhook delivery
- CSV import support

**Benefit:**
- Agency use cases
- Bulk operations
- Time savings
- Better UX for large clients

**Effort:** 8-12 hours

---

## Audit Archive Export

**Problem:** Can't export audit data for archival or analysis.

**Solution:**
- Export audits to JSON, CSV, or Excel
- Include all metadata and history
- Filter by date range, status, URL
- Scheduled exports via email
- S3 backup integration

**Benefit:**
- Data portability
- Compliance requirements
- Business intelligence
- Client deliverables

**Effort:** 6-8 hours

---

## Webhook Webhook (Inception)

**Problem:** Can't monitor webhook health externally.

**Solution:**
- Webhook that notifies about webhook status
- Dead letter queue notification webhook
- Health check status change webhook
- System alert webhook

**Benefit:**
- External monitoring integration
- Proactive alerting
- Better ops visibility
- Meta monitoring

**Effort:** 4-6 hours

---

## PDF Watermarking

**Problem:** Free PDFs could be redistributed.

**Solution:**
- Add watermark to non-paid audits
- "Generated for {client}" text
- QR code to verification page
- Remove watermark for paid tier

**Benefit:**
- Freemium revenue model
- Brand awareness
- Prevents abuse
- Revenue opportunity

**Effort:** 4-6 hours

---

## API Versioning

**Problem:** No way to make breaking changes safely.

**Solution:**
- Implement proper API versioning
- v1, v2 simultaneously supported
- Sunset old versions gracefully
- Version negotiation via header

**Benefit:**
- Safe evolution
- Backward compatibility
- Better client experience
- Professional API

**Effort:** 6-8 hours

---

## OAuth2 Authentication

**Problem:** Only supports bearer tokens, no OAuth2 flow.

**Solution:**
- Add Laravel Passport
- Support client credentials flow
- Support authorization code flow
- Token refresh support

**Benefit:**
- Third-party integrations
- More secure
- Industry standard
- Enterprise ready

**Effort:** 8-12 hours

---

## OpenAPI / Swagger Documentation

**Problem:** API docs are manual markdown files.

**Solution:**
- Generate OpenAPI spec from code
- Interactive Swagger UI
- Postman collection export
- Client SDK generation

**Benefit:**
- Always up-to-date docs
- Better developer experience
- SDK generation
- Professional appearance

**Effort:** 6-10 hours

---

## E2E Browser Tests (Pest v4)

**Problem:** No E2E tests actually testing in browser.

**Solution:**
- Use Pest v4 browser testing
- Test PDF preview in real browser
- Test API calls from frontend
- Visual regression testing

**Benefit:**
- Catch visual bugs
- Test real user flows
- Screenshot comparisons
- Higher quality

**Effort:** 8-12 hours

---

## Code Coverage Reporting

**Problem:** Don't know actual test coverage.

**Solution:**
- Configure PHPUnit coverage
- Generate HTML reports
- Add coverage badge to README
- Enforce minimum coverage (80%)

**Benefit:**
- Know what's tested
- Find gaps
- Quality metrics
- Confidence

**Effort:** 2-4 hours

---

## Docker Compose for Development

**Problem:** Local setup requires manual installation.

**Solution:**
- Docker Compose with all services
- PHP, Redis, MySQL, Chromium pre-configured
- One command to start: `docker-compose up`
- Consistent dev environment

**Benefit:**
- Faster onboarding
- Consistent environments
- Easier contributions
- Less "works on my machine"

**Effort:** 4-6 hours

---

## Contributing Guide

**Problem:** No guide for external contributors.

**Solution:**
- Create CONTRIBUTING.md
- Code of conduct
- PR template
- Issue templates
- Development setup guide

**Benefit:**
- More contributors
- Higher quality PRs
- Community growth
- Professional OSS project

**Effort:** 2-4 hours

---

## Release Automation

**Problem:** No automated release process.

**Solution:**
- GitHub Actions for releases
- Semantic versioning
- Automated changelog generation
- Tagged Docker images
- NPM package if needed

**Benefit:**
- Faster releases
- Consistent process
- Better tracking
- Professional workflow

**Effort:** 4-6 hours

---

## Performance Benchmarks

**Problem:** Don't know how performance changes over time.

**Solution:**
- Automated benchmark suite
- Track processing time per audit
- Track memory usage
- Track PDF generation time
- Alert on regressions

**Benefit:**
- Prevent performance regressions
- Optimize bottlenecks
- Capacity planning
- Quality control

**Effort:** 6-8 hours

---

## Client Libraries / SDKs

**Problem:** Clients must implement API calls manually.

**Solution:**
- Official PHP client
- JavaScript/TypeScript client
- Python client
- Generated from OpenAPI spec

**Benefit:**
- Better DX
- Faster integrations
- Type safety
- Professional offering

**Effort:** 12-20 hours per language

---

## Audit Sharing / Public URLs

**Problem:** Can't share audit results publicly.

**Solution:**
- Generate shareable link
- Optionally password protected
- Expiration date
- View-only access
- Embed in iframe

**Benefit:**
- Easy client sharing
- No authentication needed
- Marketing tool
- Better UX

**Effort:** 6-8 hours

---

## Priority Ranking

If implementing any nice-to-have features, suggested priority:

1. **S3 Support** (enables scaling)
2. **CORS Configuration** (enables frontend apps)
3. **Code Coverage** (improves quality)
4. **Docker Compose** (improves DX)
5. **Integration Tests** (improves confidence)
6. **Admin Dashboard** (improves ops)
7. **OpenAPI Docs** (improves DX)
8. **Scheduled Audits** (revenue opportunity)
9. **Multi-Tenant** (revenue opportunity)
10. **Everything else** (nice but not critical)

---

## Not Recommended

Some ideas that sound good but probably aren't worth it:

- **WebSocket real-time updates:** Too complex, polling is fine
- **Custom PDF rendering engine:** Browsershot works great
- **Built-in payment processing:** Use external service
- **User authentication (not just API tokens):** Out of scope
- **Content Management System:** Way out of scope
- **Mobile app:** API-first is enough

Keep the scope focused on **audit generation as a service**.
