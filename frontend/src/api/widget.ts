export interface WidgetConfig {
  logo_url: string | null
  accent_colour: string
  max_party_size: number
  advance_booking_days: number
  same_day_cutoff_minutes: number
  is_enabled: boolean
}

export interface WidgetConfigResponse {
  config: WidgetConfig
}

export interface SlotsResponse {
  slots: string[]
}

export interface OtpSendResponse {
  message: string
}

export interface OtpVerifyResponse {
  guest_token: string
}

export interface ReservationPayload {
  guest_token: string
  party_size: number
  date: string
  time: string
  guest_name: string
  guest_phone: string
}

const API_BASE = import.meta.env.VITE_API_BASE_URL ?? ''

async function widgetFetch<T>(path: string, options: RequestInit = {}): Promise<T> {
  const response = await fetch(`${API_BASE}/api/v1${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...options.headers,
    },
  })

  const data = await response.json()

  if (!response.ok) {
    const error = new Error(data?.error?.message ?? 'Une erreur est survenue.') as Error & { status: number; data: unknown }
    error.status = response.status
    error.data = data
    throw error
  }

  return data as T
}

export async function getWidgetConfig(token: string): Promise<WidgetConfigResponse> {
  return widgetFetch<WidgetConfigResponse>(`/public/widget/${token}/config`)
}

export async function getSlots(token: string, date: string): Promise<SlotsResponse> {
  return widgetFetch<SlotsResponse>(`/public/widget/${token}/slots?date=${encodeURIComponent(date)}`)
}

export async function sendOtp(token: string, phone: string): Promise<OtpSendResponse> {
  return widgetFetch<OtpSendResponse>(`/public/widget/${token}/otp/send`, {
    method: 'POST',
    body: JSON.stringify({ phone }),
  })
}

export async function verifyOtp(token: string, phone: string, code: string): Promise<OtpVerifyResponse> {
  return widgetFetch<OtpVerifyResponse>(`/public/widget/${token}/otp/verify`, {
    method: 'POST',
    body: JSON.stringify({ phone, code }),
  })
}

export async function createReservation(token: string, payload: ReservationPayload): Promise<{ reservation: unknown }> {
  return widgetFetch<{ reservation: unknown }>(`/public/widget/${token}/reservations`, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}
