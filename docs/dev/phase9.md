# Phase 9 — Task Tracking

> **Status**: Not started
> **Spec**: [docs/phases/phase9.md](../phases/phase9.md)
> **Last audit**: 2026-03-14

---

## Sprint 13 — CRM Fields & Review Request Backend (Weeks 23–24)

### Backend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P9-BE-001  | Migration: add_crm_fields_to_customers_table                                 | todo   | —     |
| P9-BE-002  | Migration: create_review_requests_table                                      | todo   | —     |
| P9-BE-003  | Migration: add_review_config_to_businesses_table                             | todo   | —     |
| P9-BE-004  | Update Customer model — fillable CRM fields, scopes (vip, blacklisted)      | todo   | —     |
| P9-BE-005  | Update CustomerResource — expose all CRM fields                              | todo   | —     |
| P9-BE-006  | Create CustomerCrmController (updateCrm, addNote, removeNote)               | todo   | —     |
| P9-BE-007  | Create UpdateCustomerCrmRequest (notes, is_vip, is_blacklisted, birthday…)  | todo   | —     |
| P9-BE-008  | Create ReviewRequest model (HasUuids, scopes: pending, clicked, expired)    | todo   | —     |
| P9-BE-009  | Create ReviewLinkService (generate short_code, build deep-link URL)         | todo   | —     |
| P9-BE-010  | Create ReviewRequestService (shouldSend, createRequest, markClicked)        | todo   | —     |
| P9-BE-011  | Create SendReviewRequestJob (dispatch after visit, check delay_hours)       | todo   | —     |
| P9-BE-012  | Create ReviewRedirectController (GET /r/{shortCode} → deep-link + track)   | todo   | —     |
| P9-BE-013  | Create ReviewSettingsController (show, update review config)                | todo   | —     |
| P9-BE-014  | Create UpdateReviewSettingsRequest (platform, delay_hours, place IDs)       | todo   | —     |
| P9-BE-015  | Create ReviewStatsResource (requests_sent, clicks, click_rate, by_platform) | todo   | —     |
| P9-BE-016  | Register /r/{shortCode} public route in routes/web.php                      | todo   | —     |
| P9-BE-017  | Add review request trigger to reservation completion event/observer         | todo   | —     |
| P9-BE-018  | Create PurgeExpiredReviewRequests Artisan command (weekly scheduler)        | todo   | —     |
| P9-BE-019  | Add blacklisted customer 422 guard to reservation creation flow             | todo   | —     |

### Frontend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P9-FE-001  | Create src/api/customerCrm.ts (updateCrm, getCustomerHistory)               | todo   | —     |
| P9-FE-002  | Create src/composables/useCustomerCrm.ts (fetch, update, reactive state)    | todo   | —     |
| P9-FE-003  | Create CustomerCrmPanel.vue (notes editor, VIP toggle, blacklist toggle)    | todo   | —     |
| P9-FE-004  | Create BlacklistWarningBanner.vue (shown on reservation form if blacklisted) | todo   | —     |
| P9-FE-005  | Create CustomerVipBadge.vue (VIP star/crown badge component)                | todo   | —     |
| P9-FE-006  | Update CustomerDetailView.vue — embed CustomerCrmPanel                      | todo   | —     |
| P9-FE-007  | Create src/api/reviewSettings.ts (getSettings, updateSettings, getStats)    | todo   | —     |
| P9-FE-008  | Create src/composables/useReviewSettings.ts                                 | todo   | —     |
| P9-FE-009  | Create ReputationView.vue (main review/reputation dashboard page)           | todo   | —     |
| P9-FE-010  | Create ReviewSettingsCard.vue (platform, delay, place IDs config form)      | todo   | —     |
| P9-FE-011  | Create ReviewStatsBar.vue (sent, clicked, click rate metrics)               | todo   | —     |
| P9-FE-012  | Add /leo/reputation route to router/index.ts                                | todo   | —     |
| P9-FE-013  | Update sidebar navigation — add Réputation link                             | todo   | —     |

---

## Sprint 14 — CRM Dashboard & Reputation Polish (Weeks 25–26)

### Backend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P9-BE-020  | Add CRM filter to CustomerController::index (vip=true, blacklisted=true)    | todo   | —     |
| P9-BE-021  | Create CustomerLifetimeValueResource (visits, revenue, last_visit_at)       | todo   | —     |
| P9-BE-022  | Add birthday-based filter to customer list API                               | todo   | —     |
| P9-BE-023  | Create ReviewRequestResource (for admin list view)                           | todo   | —     |
| P9-BE-024  | Add review request list endpoint to ReviewSettingsController                 | todo   | —     |

### Frontend

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P9-FE-020  | Create CustomerLifetimeValueCard.vue (visits, total spend, last visit)      | todo   | —     |
| P9-FE-021  | Update CustomerListView.vue — add VIP/blacklist filter badges + filters     | todo   | —     |
| P9-FE-022  | Create BirthdayFilterPanel.vue (show customers with birthday this month)    | todo   | —     |
| P9-FE-023  | Create ReviewRequestListView.vue (table of sent requests with click status) | todo   | —     |
| P9-FE-024  | Update LeoView.vue — add Réputation section link card                       | todo   | —     |

### DevOps

| ID         | Task                                                                         | Status | Owner |
|------------|------------------------------------------------------------------------------|--------|-------|
| P9-DO-001  | Add REVIEW_REQUEST_BASE_URL to .env.example                                 | todo   | —     |
| P9-DO-002  | Add GOOGLE_PLACES_API_KEY to .env.example (optional, for place validation)  | todo   | —     |
| P9-DO-003  | Update CI with fake review env vars                                          | todo   | —     |
| P9-DO-004  | Document /r/{shortCode} redirect route in Audit Notes (no auth required)    | todo   | —     |

---

## Audit Notes

| Date       | Note                                                                              |
|------------|-----------------------------------------------------------------------------------|
| 2026-03-14 | Initial generation — Phase 9 CRM + Reputation spec based on brainstorm 2026-03-14 |
| 2026-03-14 | Key decisions: CRM fields on customers table (notes, VIP, blacklist, birthday, preferred_table_notes); review requests via SMS post-visit; deep-link to Google/TripAdvisor; short_code redirect /r/{code}; blacklisted customers blocked at reservation creation |
