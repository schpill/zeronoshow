# APEX Context

**Task ID**: 10-booking-widget
**Timestamp**: 2026-03-14
**Task**: Phase 10 — Booking Widget (Public Self-Booking & Iframe Embed)
**Flags**: -a -x -s -t -b -pr -ci
**Working Directory**: /home/gerald/zeronoshow
**Acceptance Criteria**:
- Public unauthenticated API endpoints serve slot availability and accept reservations
- SMS OTP verification flow completes for guest phone number
- reservations.source field persists origin of booking
- Widget settings (logo, accent, rules) configurable from dashboard
- Frontend: multi-step public booking flow (date → guest → OTP → confirm → success)
- Frontend: dashboard widget settings card + embed code card
- Sprint 16: iframe embed, source filter on reservations, widget stats, accent colour CSS custom property
- All tests pass (Pest backend ≥90%, Vitest frontend ≥85%)
- CI pipeline green
