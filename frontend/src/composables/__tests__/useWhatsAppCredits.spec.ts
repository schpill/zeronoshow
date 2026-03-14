import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useWhatsAppCredits } from '@/composables/useWhatsAppCredits'
import { apiClient } from '@/api/axios'

vi.mock('@/api/axios', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
  },
}))

describe('useWhatsAppCredits', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('initializes with default values', () => {
    const { status, loading, error } = useWhatsAppCredits()
    expect(status.value).toBeNull()
    expect(loading.value).toBe(false)
    expect(error.value).toBeNull()
  })

  it('fetches status successfully', async () => {
    const mockStatus = {
      balance_cents: 500,
      balance_euros: 5,
      monthly_cap_cents: 1000,
      monthly_cap_euros: 10,
      auto_renew: true,
      is_channel_active: true,
      low_balance_warning: false,
    }
    vi.mocked(apiClient.get).mockResolvedValue(mockStatus)

    const { status, fetchStatus, balanceFormatted, balancePercent } = useWhatsAppCredits()
    await fetchStatus()

    expect(status.value).toEqual(mockStatus)
    expect(balanceFormatted.value).toContain('5,00')
    expect(balancePercent.value).toBe(50)
  })

  it('handles fetch error', async () => {
    vi.mocked(apiClient.get).mockRejectedValue(new Error('API Error'))

    const { error, fetchStatus } = useWhatsAppCredits()
    await fetchStatus()

    expect(error.value).toBe('API Error')
  })

  it('initiates top up and redirects', async () => {
    vi.mocked(apiClient.post).mockResolvedValue({ checkout_url: 'https://stripe.com/pay' })
    const originalLocation = window.location
    // @ts-expect-error - Mocking location
    delete window.location
    window.location = { href: '' } as unknown as Location

    const { topUp } = useWhatsAppCredits()
    await topUp(2000)

    expect(apiClient.post).toHaveBeenCalledWith('/leo/whatsapp/credits/topup', {
      amount_cents: 2000,
    })
    expect(window.location.href).toBe('https://stripe.com/pay')

    window.location = originalLocation
  })
})
