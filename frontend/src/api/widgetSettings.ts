import { apiClient } from '@/api/axios'

export interface WidgetSettingsRecord {
  id: string
  business_id: string
  logo_url: string | null
  accent_colour: string
  max_party_size: number
  advance_booking_days: number
  same_day_cutoff_minutes: number
  is_enabled: boolean
  embed_url: string | null
  booking_url: string | null
  created_at: string | null
  updated_at: string | null
}

export interface WidgetSettingsResponse {
  setting: WidgetSettingsRecord
}

export interface WidgetStatsResponse {
  widget_reservations_count: number
  widget_reservations_this_month: number
  widget_conversion_rate: number
}

export interface UpdateWidgetSettingsPayload {
  logo_url?: string | null
  accent_colour?: string
  max_party_size?: number
  advance_booking_days?: number
  same_day_cutoff_minutes?: number
  is_enabled?: boolean
}

export async function getWidgetSettings(businessId: string): Promise<WidgetSettingsResponse> {
  return apiClient.get<WidgetSettingsResponse>(`/businesses/${businessId}/widget`)
}

export async function updateWidgetSettings(
  businessId: string,
  payload: UpdateWidgetSettingsPayload,
): Promise<WidgetSettingsResponse> {
  return apiClient.patch<WidgetSettingsResponse>(`/businesses/${businessId}/widget`, payload)
}

export async function getWidgetStats(businessId: string): Promise<WidgetStatsResponse> {
  return apiClient.get<WidgetStatsResponse>(`/businesses/${businessId}/widget/stats`)
}
