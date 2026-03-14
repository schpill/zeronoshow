# Phase 10 — Task Tracking

> **Status**: Implemented
> **Spec**: [docs/phases/phase10.md](../phases/phase10.md)
> **Last audit**: 2026-03-14

---

## Sprint 15 — Public Booking API & Widget Core (Weeks 27–28)

### Backend

| ID          | Task                                                                          | Status | Owner |
|-------------|-------------------------------------------------------------------------------|--------|-------|
| P10-BE-001  | Migration: add_source_to_reservations_table (ENUM manual/widget)              | done   | Gerald |
| P10-BE-002  | Migration: create_booking_otps_table                                          | done   | Gerald |
| P10-BE-003  | Migration: create_widget_settings_table                                       | done   | Gerald |
| P10-BE-004  | Create BookingOtp model (HasUuids, scopes: valid, expired)                    | done   | Gerald |
| P10-BE-005  | Create WidgetSetting model (HasUuids, getPublicConfigAttribute)               | done   | Gerald |
| P10-BE-006  | Create BookingOtpService (send, verify, rate-limit, exceptions)               | done   | Gerald |
| P10-BE-007  | Create SendBookingOtpSms job (via SmsServiceInterface, retry 2x)              | done   | Gerald |
| P10-BE-008  | Create SlotAvailabilityService (getAvailableSlots, Redis cache 30s TTL)       | done   | Gerald |
| P10-BE-009  | Create PublicBookingController (config, slots, sendOtp, verifyOtp, store)     | done   | Gerald |
| P10-BE-010  | Migration: add_public_token_to_businesses_table (UUID unique)                 | done   | Gerald |
| P10-BE-011  | Create SendOtpRequest (phone E.164 validation, throttle 3/10min)              | done   | Gerald |
| P10-BE-012  | Create VerifyOtpRequest (phone + 6-digit code validation)                     | done   | Gerald |
| P10-BE-013  | Create PublicStoreReservationRequest (guest_token + reservation fields)       | done   | Gerald |
| P10-BE-014  | Create GuestToken service (HMAC-SHA256 JWT issue + verify)                    | done   | Gerald |
| P10-BE-015  | Create WidgetSettingController (show, update — authenticated)                 | done   | Gerald |
| P10-BE-016  | Create UpdateWidgetSettingRequest (logo_url, accent_colour, rules)            | done   | Gerald |
| P10-BE-017  | Create WidgetSettingResource (embed_url, booking_url computed)                | done   | Gerald |
| P10-BE-018  | Create PurgeExpiredBookingOtps Artisan command (daily scheduler)              | done   | Gerald |
| P10-BE-019  | Register public widget routes + authenticated widget settings routes          | done   | Gerald |

### Frontend

| ID          | Task                                                                          | Status | Owner |
|-------------|-------------------------------------------------------------------------------|--------|-------|
| P10-FE-001  | Create src/api/widget.ts (public widget API client, no auth)                  | done   | Gerald |
| P10-FE-002  | Create BookingWidgetView.vue (public full-page booking flow)                  | done   | Gerald |
| P10-FE-003  | Create BookingSuccessView.vue (confirmation page after booking)               | done   | Gerald |
| P10-FE-004  | Create BookingStepDate.vue (calendar + time slot grid)                        | done   | Gerald |
| P10-FE-005  | Create BookingStepGuest.vue (guest details form)                              | done   | Gerald |
| P10-FE-006  | Create BookingStepOtp.vue (6-input OTP with auto-focus + resend)              | done   | Gerald |
| P10-FE-007  | Create BookingStepConfirm.vue (summary + confirm button + 409 handling)       | done   | Gerald |
| P10-FE-008  | Create useBookingWidget.ts composable (multi-step flow state)                 | done   | Gerald |
| P10-FE-009  | Add public route /widget/:businessToken to router/index.ts                    | done   | Gerald |
| P10-FE-010  | Create WidgetSettingsCard.vue (dashboard card, opens settings modal)          | done   | Gerald |
| P10-FE-011  | Create WidgetSettingsModal.vue (form: logo, accent, rules, enabled toggle)    | done   | Gerald |
| P10-FE-012  | Create WidgetEmbedCard.vue (booking URL + iframe code copy buttons)           | done   | Gerald |
| P10-FE-013  | Create src/api/widgetSettings.ts (authenticated widget settings client)       | done   | Gerald |
| P10-FE-014  | Create useWidgetSettings.ts composable                                        | done   | Gerald |
| P10-FE-015  | Update LeoView.vue — add Widget de réservation section                        | done   | Gerald |

### DevOps

| ID          | Task                                                                          | Status | Owner |
|-------------|-------------------------------------------------------------------------------|--------|-------|
| P10-DO-001  | Add VITE_WIDGET_BASE_URL to .env.example                                      | done   | Gerald |
| P10-DO-002  | Update nginx.conf — verify /widget/* SPA catch-all does not conflict with API | done   | Gerald |
| P10-DO-003  | Add BOOKING_OTP_TTL_MINUTES to .env.example                                   | done   | Gerald |

---

## Sprint 16 — Widget Polish, Embed & Dashboard Integration (Weeks 29–30)

### Backend

| ID          | Task                                                                          | Status | Owner |
|-------------|-------------------------------------------------------------------------------|--------|-------|
| P10-BE-020  | Update ReservationResource — expose source field in collection               | done   | Gerald |
| P10-BE-021  | Update ReservationController::index() — add filter[source] support           | done   | Gerald |
| P10-BE-022  | Create WidgetStatsResource (total, this_month, conversion_rate)              | done   | Gerald |
| P10-BE-023  | Add stats() method to WidgetSettingController                                 | done   | Gerald |
| P10-BE-024  | Create AllowIframeForWidget middleware (X-Frame-Options: ALLOWALL for widget) | done   | Gerald |
| P10-BE-025  | Create booking:purge-otps command alias with --dry-run flag                  | done   | Gerald |
| P10-BE-026  | Create BookingWidgetSeeder (test business + widget settings + OTPs)          | done   | Gerald |
| P10-BE-027  | Add widget_reservations_count to BusinessResource (no N+1)                   | done   | Gerald |

### Frontend

| ID          | Task                                                                          | Status | Owner |
|-------------|-------------------------------------------------------------------------------|--------|-------|
| P10-FE-020  | Update BookingWidgetView.vue — apply accent_colour as CSS custom property     | done   | Gerald |
| P10-FE-021  | Create WidgetIframeEntrypoint.vue (postMessage: zns:resize, zns:booked)      | done   | Gerald |
| P10-FE-022  | Update WidgetEmbedCard.vue — separate iframe/direct-link copy buttons        | done   | Gerald |
| P10-FE-023  | Update ReservationListView.vue — Source column + filter dropdown             | done   | Gerald |
| P10-FE-024  | Create WidgetStatsCard.vue (counts + conversion rate)                        | done   | Gerald |
| P10-FE-025  | Update LeoView.vue — add WidgetStatsCard next to WidgetSettingsCard          | done   | Gerald |
| P10-FE-026  | Add Playwright E2E test e2e/booking-widget.spec.ts (full booking flow)       | done   | Gerald |
| P10-FE-027  | Update useReservations.ts — add sourceFilter ref + query param               | done   | Gerald |

### DevOps

| ID          | Task                                                                          | Status | Owner |
|-------------|-------------------------------------------------------------------------------|--------|-------|
| P10-DO-020  | Add CI step for Playwright booking-widget E2E test                            | done   | Gerald |
| P10-DO-021  | Add PUBLIC_WIDGET_OTP_MOCK_CODE to .env.example (dev/test only)               | done   | Gerald |
| P10-DO-022  | Document widget disable, public_token regeneration, iframe CSP setup          | done   | Gerald |

---

## Audit Notes

| Date       | Note                                                                              |
|------------|-----------------------------------------------------------------------------------|
| 2026-03-14 | Initial generation — Phase 10 Booking Widget spec based on brainstorm 2026-03-14 |
| 2026-03-14 | Key decisions: widget included in Léo add-on (no extra paywall); SMS OTP verification before booking; slot cache TTL 30s; public_token UUID on businesses; X-Frame-Options override for iframe embed; accent colour via CSS custom property |
