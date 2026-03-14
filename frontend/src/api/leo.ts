import { apiClient } from '@/api/axios'
import type { LeoAddonStatus, LeoChannelRecord } from '@/types/leo'

export interface LeoChannelPayload {
  channel: LeoChannelRecord['channel']
  external_identifier: string
  bot_name: string
  monthly_cap_cents?: number
  auto_renew?: boolean
}

export interface LeoChannelUpdatePayload {
  bot_name?: string
  is_active?: boolean
}

export async function getLeoChannel() {
  return apiClient.get<{ channel: LeoChannelRecord | null }>('/leo/channels')
}

export async function createLeoChannel(payload: LeoChannelPayload) {
  return apiClient.post<{ channel: LeoChannelRecord }>('/leo/channels', payload)
}

export async function updateLeoChannel(id: string, payload: LeoChannelUpdatePayload) {
  return apiClient.patch<{ channel: LeoChannelRecord }>(`/leo/channels/${id}`, payload)
}

export async function deleteLeoChannel(id: string) {
  return apiClient.delete<void>(`/leo/channels/${id}`)
}

export async function activateLeoAddon() {
  return apiClient.post<{ activated: boolean; checkout_url: string | null }>('/leo/addon/activate')
}

export async function deactivateLeoAddon() {
  return apiClient.post<void>('/leo/addon/deactivate')
}

export async function getLeoAddonStatus() {
  return apiClient.get<LeoAddonStatus>('/leo/addon-status')
}
