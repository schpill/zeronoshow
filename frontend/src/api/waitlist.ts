import { apiClient } from './axios'

export interface WaitlistEntry {
  id: string
  business_id: string
  slot_date: string
  slot_time: string
  client_name: string
  client_phone: string
  party_size: number
  status: 'pending' | 'notified' | 'confirmed' | 'declined' | 'expired'
  status_label: string
  channel: 'sms' | 'whatsapp'
  priority_order: number
  notified_at?: string
  expires_at?: string
  confirmed_at?: string
  created_at: string
}

export interface WaitlistFilter {
  slot_date?: string
  status?: string
  page?: number
}

export interface CreateWaitlistEntryPayload {
  slot_date: string
  slot_time: string
  client_name: string
  client_phone: string
  party_size: number
}

export interface ApiResponse<T> {
  data: T
}

export interface WaitlistEntriesResponse {
  data: WaitlistEntry[]
  meta: {
    current_page: number
    last_page: number
    total: number
  }
}

export interface PublicWaitlistInfo {
  business_name: string
  slots_available: { date: string; times: string[] }[]
}

export interface WaitlistSettingsUpdateResponse {
  message: string
  settings: Partial<WaitlistSettings>
}

export interface RegenerateLinkResponse {
  message: string
  waitlist_public_token: string
  public_registration_url: string
}

export const getWaitlistEntries = async (params?: WaitlistFilter): Promise<WaitlistEntriesResponse> => {
  let path = '/waitlist'
  if (params) {
    const query = new URLSearchParams()
    if (params.slot_date) query.append('slot_date', params.slot_date)
    if (params.status) query.append('status', params.status)
    if (params.page) query.append('page', params.page.toString())
    path += `?${query.toString()}`
  }
  const response = await apiClient.get<WaitlistEntriesResponse>(path)
  return response
}

export const addWaitlistEntry = async (
  payload: CreateWaitlistEntryPayload,
): Promise<ApiResponse<WaitlistEntry>> => {
  const response = await apiClient.post<ApiResponse<WaitlistEntry>>('/waitlist', payload)
  return response
}

export const removeWaitlistEntry = async (id: string) => {
  await apiClient.delete(`/waitlist/${id}`)
}

export const reorderWaitlist = async (orderedIds: string[]) => {
  const response = await apiClient.post('/waitlist/reorder', { ordered_ids: orderedIds })
  return response
}

export const notifyEntry = async (id: string) => {
  const response = await apiClient.post(`/waitlist/${id}/notify`)
  return response
}

export interface WaitlistSettings {
  waitlist_enabled: boolean
  waitlist_notification_window_minutes: number
  waitlist_public_token?: string
  public_registration_url?: string
}

export const getWaitlistSettings = async (): Promise<WaitlistSettings> => {
  const response = await apiClient.get<WaitlistSettings>('/waitlist/settings')
  return response
}

export const updateWaitlistSettings = async (
  payload: Partial<WaitlistSettings>,
): Promise<WaitlistSettingsUpdateResponse> => {
  const response = await apiClient.patch<WaitlistSettingsUpdateResponse>(
    '/waitlist/settings',
    payload,
  )
  return response
}

export const regeneratePublicLink = async (): Promise<RegenerateLinkResponse> => {
  const response = await apiClient.post<RegenerateLinkResponse>('/waitlist/settings/regenerate-link')
  return response
}

export const getPublicWaitlistInfo = async (token: string): Promise<PublicWaitlistInfo> => {
  const response = await apiClient.get<PublicWaitlistInfo>(`/join/${token}`)
  return response
}

export const joinWaitlistPublic = async (
  token: string,
  payload: CreateWaitlistEntryPayload,
): Promise<ApiResponse<WaitlistEntry>> => {
  const response = await apiClient.post<ApiResponse<WaitlistEntry>>(`/join/${token}`, payload)
  return response
}
