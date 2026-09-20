# Browser security headers and CSP rollout

Status date: 20 September 2026. This document describes the Phase 1 application
candidate. Production remains on the Phase 0 revision until an exact cumulative
commit is approved and deployed.

## Response boundary

`AddBrowserSecurityHeaders` runs as global Laravel middleware and adds these headers
to application-generated responses:

| Header | Candidate value | Behavior |
| --- | --- | --- |
| `X-Content-Type-Options` | `nosniff` | Enforced MIME-sniffing defense |
| `X-Frame-Options` | `SAMEORIGIN` | Enforced same-origin framing boundary |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Limits cross-origin referrer detail |
| `Permissions-Policy` | disables camera, microphone, geolocation, payment, and USB | Denies unused browser capabilities |
| `Strict-Transport-Security` | `max-age=86400` | Added only when Laravel recognizes the request as HTTPS; no subdomain or preload scope |
| `Content-Security-Policy-Report-Only` | policy in `config/security.php` | Reports violations without blocking resources |
| `Reporting-Endpoints` | configured application URL plus `/csp-reports` | Modern Reporting API destination |

This middleware does not cover a response served directly by Nginx, including a
static file or an Nginx-generated error. Phase 3 must add matching, tested edge/origin
headers after the application policy has produced usable compatibility evidence.

## Initial report-only policy

The initial policy protects or measures `default-src`, `base-uri`, `object-src`,
`frame-ancestors`, `form-action`, scripts, styles, images, fonts, browser
connections, media, frames, workers, manifests, and mixed-content upgrades. It also
sends both legacy `report-uri` and modern `report-to` reports.

The audited UI still contains extensive inline Blade scripts and styles. The initial
report-only policy therefore includes `unsafe-inline` for scripts and styles. It
does not include `unsafe-eval`, so runtime expression compilation remains visible
in telemetry. Images and media currently accept arbitrary HTTPS provider URLs;
Reverb may use `ws:` or `wss:` based on environment configuration. These allowances
are documented migration debt, not an acceptable final enforced policy.

Before enforcement:

1. exercise public, authentication, dashboard, inbox, settings, Instagram, upload,
   download, and Reverb journeys on the exact deployed revision;
2. aggregate violations by effective directive, blocked origin, and source path;
3. extract inline scripts/styles or introduce reviewed nonces/hashes;
4. replace broad `https:`, `ws:`, and `wss:` sources with the measured minimum;
5. verify third-party media and fonts, OAuth redirects, WebSockets, and Vite assets;
6. approve a separate change that promotes the measured policy to the enforced
   `Content-Security-Policy` header.

Do not promote the policy merely because report volume is low. Confirm that the
receiver, sampling traffic, and log routing are working first.

## Report receiver privacy and abuse controls

`POST /csp-reports` accepts legacy CSP reports and Reporting API batches. It is
public because browsers cannot attach an application CSRF token. Compensating
controls are:

- `60` requests per minute per limiter key;
- maximum request body of `65536` bytes;
- maximum `20` reports processed from one batch;
- JSON-only parsing with a bounded nesting depth;
- an allowlist of diagnostic fields;
- removal of URL query strings, fragments, and user information;
- omission of `script-sample`, original policy text, request body, cookies, IP,
  authorization data, and arbitrary report keys;
- structured warning logs only after normalization.

The receiver returns `204` for valid reports, `400` for malformed JSON, and `413`
for an oversized body. Never expand logging to raw browser reports without a new
privacy and secret-exposure review.

## Configuration and rollback

The safe defaults live in `src/config/security.php`. Environment overrides are
documented in `ENVIRONMENT_VARIABLES.md`.

- Set `CSP_REPORT_ONLY_ENABLED=false` to stop emitting CSP and reporting headers
  while retaining the other browser controls.
- Set `SECURITY_HEADERS_ENABLED=false` only for an approved emergency rollback of
  the entire application header layer.
- Set `SECURITY_HSTS_ENABLED=false` to stop sending future HSTS headers. Browsers
  that already cached HSTS retain it until the advertised one-day `max-age` ends.

After any environment change, rebuild Laravel configuration cache and verify the
public response. Do not add `includeSubDomains`, preload, or a longer HSTS lifetime
until every affected hostname and rollback constraint is approved.

## Deployment verification

Verify the exact release revision first, then inspect headers without printing
cookies or response bodies:

```bash
curl --fail --silent --show-error --dump-header - --output /dev/null https://mini.leadochat.com/
```

Confirm that `Content-Security-Policy` is absent, the report-only header is present,
the `Reporting-Endpoints` URL uses the canonical HTTPS origin, and HSTS has the
staged one-day lifetime. Submit only a synthetic CSP report when testing the
receiver, then verify the normalized log record contains no query string or sample.

Rollback or forward-fix evaluation is required for missing critical assets,
authentication failures, broken inbox/social interaction, Reverb failure, excessive
report ingestion, sensitive data in telemetry, or unexpected framing/integration
breakage.
