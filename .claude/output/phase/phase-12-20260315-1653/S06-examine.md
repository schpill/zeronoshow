## Adversarial Examination

Review scope:
- Admin auth
- Token storage and transport
- Impersonation flow
- Swagger/docs exposure
- Route protection and regressions

Findings:

1. High — admin bearer token is persisted in `localStorage`, which makes full operator access recoverable by any XSS payload in the SPA.
   - [frontend/src/stores/admin.ts](/home/gerald/zeronoshow/frontend/src/stores/admin.ts#L19)
   - [frontend/src/stores/admin.ts](/home/gerald/zeronoshow/frontend/src/stores/admin.ts#L26)
   - [frontend/src/api/adminAxios.ts](/home/gerald/zeronoshow/frontend/src/api/adminAxios.ts#L21)
   - Reasoning: Phase 12 introduces a high-privilege backoffice. Persisting the raw Sanctum token in `localStorage` substantially increases blast radius compared with in-memory or hardened cookie/session approaches.

2. Medium — Swagger UI and raw docs routes are publicly exposed with no production middleware guard.
   - [backend/config/l5-swagger.php](/home/gerald/zeronoshow/backend/config/l5-swagger.php#L18)
   - [backend/config/l5-swagger.php](/home/gerald/zeronoshow/backend/config/l5-swagger.php#L71)
   - Reasoning: `/api/docs`, `/docs`, and static assets are reachable without auth. The phase risk register explicitly called out protecting docs in production; current config leaves them open everywhere.

Resolved during S07:
- Impersonation bootstrap now moves the token into `sessionStorage`, updates the business app auth state before guard checks, and strips the query string.
- Admin login now also has route-level throttle middleware as a fallback in addition to the Redis lockout logic.

Residual risks:
- No dedicated automated regression test currently proves a business-scoped token gets `403` on `/api/v1/admin/*`.
- Manual verification remains pending for business-token rejection on admin endpoints, impersonation expiry, docs exposure policy in production, and admin lockout behavior end-to-end.
