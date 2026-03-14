# Phase 9 — Task Tracking

> **Status**: Implemented
> **Spec**: [docs/phases/phase9.md](../phases/phase9.md)
> **Last audit**: 2026-03-14

---

## Sprint 13 — CRM Fields & Review Request Backend (Weeks 23–24)

### Backend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P9-BE-001  | Migration: add_crm_fields_to_customers_table                                 | done   | Codex |
| P9-BE-002  | Migration: create_review_requests_table                                      | done   | Codex |
| P9-BE-003  | Migration: add_review_config_to_businesses_table                             | done   | Codex |
| P9-BE-004  | Update Customer model — fillable CRM fields, scopes (vip, blacklisted)      | done   | Codex |
| P9-BE-005  | Update CustomerResource — expose all CRM fields                              | done   | Codex |
| P9-BE-006  | Create CustomerCrmController (updateCrm, addNote, removeNote)               | done   | Codex |
| P9-BE-007  | Create UpdateCustomerCrmRequest (notes, is_vip, is_blacklisted, birthday…)  | done   | Codex |
| P9-BE-008  | Create ReviewRequest model (HasUuids, scopes: pending, clicked, expired)    | done   | Codex |
| P9-BE-009  | Create ReviewLinkService (generate short_code, build deep-link URL)         | done   | Codex |
| P9-BE-010  | Create ReviewRequestService (shouldSend, createRequest, markClicked)        | done   | Codex |
| P9-BE-011  | Create SendReviewRequestJob (dispatch after visit, check delay_hours)       | done   | Codex |
| P9-BE-012  | Create ReviewRedirectController (GET /r/{shortCode} → deep-link + track)   | done   | Codex |
| P9-BE-013  | Create ReviewSettingsController (show, update review config)                | done   | Codex |
| P9-BE-014  | Create UpdateReviewSettingsRequest (platform, delay_hours, place IDs)       | done   | Codex |
| P9-BE-015  | Create ReviewStatsResource (requests_sent, clicks, click_rate, by_platform) | done   | Codex |
| P9-BE-016  | Register /r/{shortCode} public route in routes/web.php                      | done   | Codex |
| P9-BE-017  | Add review request trigger to reservation completion event/observer         | done   | Codex |
| P9-BE-018  | Create PurgeExpiredReviewRequests Artisan command (weekly scheduler)        | done   | Codex |
| P9-BE-019  | Add blacklisted customer 422 guard to reservation creation flow             | done   | Codex |

### Frontend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P9-FE-001  | Create src/api/customerCrm.ts (updateCrm, getCustomerHistory)               | done   | Codex |
| P9-FE-002  | Create src/composables/useCustomerCrm.ts (fetch, update, reactive state)    | done   | Codex |
| P9-FE-003  | Create CustomerCrmPanel.vue (notes editor, VIP toggle, blacklist toggle)    | done   | Codex |
| P9-FE-004  | Create BlacklistWarningBanner.vue (shown on reservation form if blacklisted) | done   | Codex |
| P9-FE-005  | Create CustomerVipBadge.vue (VIP star/crown badge component)                | done   | Codex |
| P9-FE-006  | Update CustomerDetailView.vue — embed CustomerCrmPanel                      | done   | Codex |
| P9-FE-007  | Create src/api/reviewSettings.ts (getSettings, updateSettings, getStats)    | done   | Codex |
| P9-FE-008  | Create src/composables/useReviewSettings.ts                                 | done   | Codex |
| P9-FE-009  | Create ReputationView.vue (main review/reputation dashboard page)           | done   | Codex |
| P9-FE-010  | Create ReviewSettingsCard.vue (platform, delay, place IDs config form)      | done   | Codex |
| P9-FE-011  | Create ReviewStatsBar.vue (sent, clicked, click rate metrics)               | done   | Codex |
| P9-FE-012  | Add /leo/reputation route to router/index.ts                                | done   | Codex |
| P9-FE-013  | Update sidebar navigation — add Réputation link                             | done   | Codex |

---

## Sprint 14 — CRM Dashboard & Reputation Polish (Weeks 25–26)

### Backend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P9-BE-020  | Add CRM filter to CustomerController::index (vip=true, blacklisted=true)    | done   | Codex |
| P9-BE-021  | Create CustomerLifetimeValueResource (visits, revenue, last_visit_at)       | deferred | —   |
| P9-BE-022  | Add birthday-based filter to customer list API                               | done   | Codex |
| P9-BE-023  | Create ReviewRequestResource (for admin list view)                           | done   | Codex |
| P9-BE-024  | Add review request list endpoint to ReviewSettingsController                 | done   | Codex |

### Frontend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P9-FE-020  | Create CustomerLifetimeValueCard.vue (visits, total spend, last visit)      | deferred | —   |
| P9-FE-021  | Update CustomerListView.vue — add VIP/blacklist filter badges + filters     | done   | Codex |
| P9-FE-022  | Create BirthdayFilterPanel.vue (show customers with birthday this month)    | done   | Codex |
| P9-FE-023  | Create ReviewRequestListView.vue (table of sent requests with click status) | done   | Codex |
| P9-FE-024  | Update LeoView.vue — add Réputation section link card                       | done   | Codex |

### DevOps

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P9-DO-001  | Add REVIEW_REQUEST_BASE_URL to .env.example                                 | done   | Codex |
| P9-DO-002  | Add GOOGLE_PLACES_API_KEY to .env.example (optional, for place validation)  | done   | Codex |
| P9-DO-003  | Update CI with fake review env vars                                          | done   | Codex |
| P9-DO-004  | Document /r/{shortCode} redirect route in Audit Notes (no auth required)    | done   | Codex |

---

## Audit Notes

| Date       | Note                                                                              |
|------------|-----------------------------------------------------------------------------------|
| 2026-03-14 | Initial generation — Phase 9 CRM + Reputation spec based on brainstorm 2026-03-14 |
| 2026-03-14 | Key decisions: CRM fields on customers table (notes, VIP, blacklist, birthday, preferred_table_notes); review requests via SMS post-visit; deep-link to Google/TripAdvisor; short_code redirect /r/{code}; blacklisted customers blocked at reservation creation |
| 2026-03-14 | Implementation delivered on branch `apex/phase9-lightweight-crm-reputation`: backend CRM/review APIs, observer/job/redirect flow, Customers/Reputation frontend views, reservation blacklist warning, and targeted backend/frontend validation green. |
| 2026-03-14 | Integration note: the repo had no `CustomerListView`/`CustomerDetailView`; Phase 9 UI was anchored to new `CustomersView`, existing `ReservationDetailPage`, and new `/reputation` route in current app structure. |
| 2026-03-14 | Behavioral note: blacklist behavior follows detailed spec warning semantics in creation/detail flows instead of blocking reservation creation. |
| 2026-03-14 | Tracking note: lifetime value resource/card remain deferred because they are absent from the detailed phase spec and the current data model has no spend source to implement them correctly without inventing semantics. |
