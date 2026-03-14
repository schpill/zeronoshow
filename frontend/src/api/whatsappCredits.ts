import { apiClient } from '@/api/axios'

export interface WhatsAppCreditStatus {
  balance_cents: number
  balance_euros: number
  monthly_cap_cents: number
  monthly_cap_euros: number
  auto_renew: boolean
  is_channel_active: boolean
  low_balance_warning: boolean
}

export async function getWhatsAppCreditStatus(): Promise<WhatsAppCreditStatus> {
  return apiClient.get<WhatsAppCreditStatus>('/leo/whatsapp/credits')
}

export async function initiateTopUp(amountCents: number): Promise<{ checkout_url: string }> {
  return apiClient.post<{ checkout_url: string }>('/leo/whatsapp/credits/topup', {
    amount_cents: amountCents,
  })
}

export async function setWhatsAppMonthlyCap(
  capCents: number,
  autoRenew: boolean,
): Promise<WhatsAppCreditStatus> {
  return apiClient.patch<WhatsAppCreditStatus>('/leo/whatsapp/credits/cap', {
    monthly_cap_cents: capCents,
    auto_renew: autoRenew,
  })
}
