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

export const getWaitlistEntries = async (params?: WaitlistFilter) => {
  let path = '/waitlist'
  if (params) {
    const query = new URLSearchParams()
    if (params.slot_date) query.append('slot_date', params.slot_date)
    if (params.status) query.append('status', params.status)
    if (params.page) query.append('page', params.page.toString())
    path += `?${query.toString()}`
  }
  const response = await apiClient.get(path)
  return response
}

export const addWaitlistEntry = async (payload: CreateWaitlistEntryPayload) => {
  const response = await apiClient.post('/waitlist', payload)
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

export const getWaitlistSettings = async () => {
  const response = await apiClient.get('/waitlist/settings')
  return response
}

export const updateWaitlistSettings = async (payload: Partial<WaitlistSettings>) => {
  const response = await apiClient.patch('/waitlist/settings', payload)
  return response
}

export const regeneratePublicLink = async () => {
  const response = await apiClient.post('/waitlist/settings/regenerate-link')
  return response
}

export const getPublicWaitlistInfo = async (token: string) => {
  const response = await apiClient.get(`/join/${token}`)
  return response
}

export const joinWaitlistPublic = async (token: string, payload: CreateWaitlistEntryPayload) => {
  const response = await apiClient.post(`/join/${token}`, payload)
  return response
}
