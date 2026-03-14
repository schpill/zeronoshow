import { apiClient } from '@/api/axios'
import type { ReservationCustomer } from '@/types/reservations'

export interface UpdateCustomerCrmPayload {
  notes?: string | null
  is_vip?: boolean
  is_blacklisted?: boolean
  birthday_month?: number | null
  birthday_day?: number | null
  preferred_table_notes?: string | null
}

export interface ReviewRequest {
  id: string
  reservation_id: string
  customer_name: string
  platform: 'google' | 'tripadvisor'
  status: 'pending' | 'sent' | 'clicked' | 'expired'
  short_url: string
  sent_at: string | null
  clicked_at: string | null
  expires_at: string | null
}

export interface ReviewStats {
  total_sent: number
  total_clicked: number
  click_rate_percent: number
}

export interface ReviewSettings {
  review_requests_enabled: boolean
  review_platform: 'google' | 'tripadvisor'
  review_delay_hours: number
  google_place_id: string | null
  tripadvisor_location_id: string | null
}

export type ReviewSettingsPayload = ReviewSettings

export async function updateCustomerCrm(customerId: string, payload: UpdateCustomerCrmPayload) {
  const response = await apiClient.patch<{ data: ReservationCustomer }>(
    `/customers/${customerId}/crm`,
    payload,
  )
  return response.data
}

export async function getCustomers(filters?: {
  is_vip?: boolean
  is_blacklisted?: boolean
  birthday_month?: number | null
}) {
  const search = new URLSearchParams()

  if (filters?.is_vip) search.set('filter[is_vip]', '1')
  if (filters?.is_blacklisted) search.set('filter[is_blacklisted]', '1')
  if (filters?.birthday_month) search.set('filter[birthday_month]', String(filters.birthday_month))

  const query = search.toString()
  const response = await apiClient.get<{ data: ReservationCustomer[] }>(
    `/customers${query ? `?${query}` : ''}`,
  )
  return response.data
}

export async function getReviewRequests(params?: {
  status?: string
  platform?: string
  date_from?: string
  date_to?: string
}) {
  const search = new URLSearchParams()

  Object.entries(params ?? {}).forEach(([key, value]) => {
    if (value) search.set(key, value)
  })

  const query = search.toString()
  return apiClient.get<{ data: ReviewRequest[] }>(`/review-requests${query ? `?${query}` : ''}`)
}

export async function getReviewStats() {
  return apiClient.get<ReviewStats>('/review-requests/stats')
}

export async function getReviewSettings() {
  const response = await apiClient.get<{ data: ReviewSettings }>('/review-settings')
  return response.data
}

export async function updateReviewSettings(payload: ReviewSettingsPayload) {
  const response = await apiClient.patch<{ data: ReviewSettings }>('/review-settings', payload)
  return response.data
}
