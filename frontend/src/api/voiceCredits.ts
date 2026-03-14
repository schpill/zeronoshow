import { apiClient } from '@/api/axios'

export interface VoiceCreditStatus {
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

export async function getVoiceCreditStatus(): Promise<VoiceCreditStatus> {
  return apiClient.get<{ data: VoiceCreditStatus }>('/voice/credits').then((response) => response.data)
}

export async function initiateVoiceTopUp(amountCents: number): Promise<{ checkout_url: string }> {
  return apiClient.post<{ checkout_url: string }>('/voice/credits/topup', {
    amount_cents: amountCents,
  })
}

export async function setVoiceMonthlyCap(
  capCents: number,
  autoRenew: boolean,
): Promise<VoiceCreditStatus> {
  return apiClient
    .patch<{ data: VoiceCreditStatus }>('/voice/credits/cap', {
      monthly_cap_cents: capCents,
      auto_renew: autoRenew,
    })
    .then((response) => response.data)
}
