import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useWhatsAppCredits } from '@/composables/useWhatsAppCredits'
import { apiClient } from '@/api/axios'
import type { WhatsAppCreditStatus } from '@/api/whatsappCredits'

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
    const mockStatus: WhatsAppCreditStatus = {
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

  it('handles non-Error objects in normalizeError', async () => {
    vi.mocked(apiClient.get).mockRejectedValue('String Error')

    const { error, fetchStatus } = useWhatsAppCredits()
    await fetchStatus()

    expect(error.value).toBe('Une erreur est survenue.')
  })

  it('initiates top up and redirects', async () => {
    vi.mocked(apiClient.post).mockResolvedValue({ checkout_url: 'https://stripe.com/pay' })
    const originalHref = window.location.href
    // @ts-expect-error - Mocking location
    delete window.location
    Object.defineProperty(window, 'location', {
      value: { href: '' },
      writable: true,
      configurable: true,
    })

    const { topUp } = useWhatsAppCredits()
    await topUp(2000)

    expect(apiClient.post).toHaveBeenCalledWith('/leo/whatsapp/credits/topup', {
      amount_cents: 2000,
    })
    expect(window.location.href).toBe('https://stripe.com/pay')

    Object.defineProperty(window, 'location', {
      value: { href: originalHref },
      writable: true,
      configurable: true,
    })
  })

  it('sets monthly cap successfully', async () => {
    const updatedStatus: WhatsAppCreditStatus = {
      balance_cents: 500,
      balance_euros: 5,
      monthly_cap_cents: 2000,
      monthly_cap_euros: 20,
      auto_renew: false,
      is_channel_active: true,
      low_balance_warning: false,
    }
    vi.mocked(apiClient.patch).mockResolvedValue(updatedStatus)

    const { status, setCap } = useWhatsAppCredits()
    await setCap(2000, false)

    expect(apiClient.patch).toHaveBeenCalledWith('/leo/whatsapp/credits/cap', {
      monthly_cap_cents: 2000,
      auto_renew: false,
    })
    expect(status.value).toEqual(updatedStatus)
  })

  it('handles setCap error', async () => {
    vi.mocked(apiClient.patch).mockRejectedValue(new Error('Cap Error'))

    const { error, setCap } = useWhatsAppCredits()
    await setCap(2000, false)

    expect(error.value).toBe('Cap Error')
  })

  it('calculates balance percent correctly', async () => {
    const { status, balancePercent } = useWhatsAppCredits()

    // 0 if no status
    expect(balancePercent.value).toBe(0)

    const baseStatus: WhatsAppCreditStatus = {
      balance_cents: 0,
      balance_euros: 0,
      monthly_cap_cents: 100,
      monthly_cap_euros: 1,
      auto_renew: true,
      is_channel_active: true,
      low_balance_warning: false,
    }

    status.value = { ...baseStatus, balance_cents: 150 }
    expect(balancePercent.value).toBe(100)

    status.value = { ...baseStatus, balance_cents: 50 }
    expect(balancePercent.value).toBe(50)

    status.value = { ...baseStatus, balance_cents: 0 }
    expect(balancePercent.value).toBe(0)

    status.value = { ...baseStatus, balance_cents: 50, monthly_cap_cents: 0 }
    expect(balancePercent.value).toBe(0)
  })
})
