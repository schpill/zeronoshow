import { apiClient } from '@/api/axios'

export interface VoiceCallLog {
  id: string
  reservation_id: string
  attempt_number: number
  status: string
  status_label?: string | null
  dtmf_response: string | null
  duration_seconds: number | null
  cost_cents: number | null
  created_at: string | null
}

export interface VoiceSettings {
  balance_cents: number
  balance_euros: number
  monthly_cap_cents: number
  monthly_cap_euros: number
  auto_renew: boolean
  auto_call_enabled: boolean
  auto_call_score_threshold: number | null
  auto_call_min_party_size: number | null
  retry_count: number
  retry_delay_minutes: number
  is_channel_active: boolean
  low_balance_warning: boolean
}

export interface VoiceSettingsPayload {
  auto_call_enabled: boolean
  score_threshold: number | null
  min_party_size: number | null
  retry_count: number
  retry_delay_minutes: number
}

export async function getVoiceSettings(): Promise<VoiceSettings> {
  return apiClient.get<{ data: VoiceSettings }>('/voice/settings').then((response) => response.data)
}

export async function updateVoiceSettings(payload: VoiceSettingsPayload): Promise<VoiceSettings> {
  return apiClient
    .patch<{ data: VoiceSettings }>('/voice/settings', payload)
    .then((response) => response.data)
}

export async function getVoiceCallLogs(reservationId: string): Promise<VoiceCallLog[]> {
  return apiClient
    .get<{ data: VoiceCallLog[] }>(`/reservations/${reservationId}/calls`)
    .then((response) => response.data)
}

export async function initiateVoiceCall(reservationId: string): Promise<VoiceCallLog> {
  return apiClient
    .post<{ data: VoiceCallLog }>(`/reservations/${reservationId}/call`)
    .then((response) => response.data)
}
