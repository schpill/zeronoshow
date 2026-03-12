import { beforeEach, describe, expect, it, vi } from 'vitest'

import * as api from '@/api/axios'
import { useReservations } from '@/composables/useReservations'

vi.mock('@/api/axios', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

describe('useReservations', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('calls POST /reservations with payload', async () => {
    vi.mocked(api.apiClient.post).mockResolvedValueOnce({ reservation: { id: 'res-1' } })

    const reservations = useReservations()
    const payload = {
      customer_name: 'Marc Dubois',
      phone: '+33612345678',
      scheduled_at: '2026-03-12T20:00',
      guests: 2,
    }

    await reservations.createReservation(payload)

    expect(api.apiClient.post).toHaveBeenCalledWith('/reservations', payload)
  })

  it('calls GET /reservations with date param', async () => {
    vi.mocked(api.apiClient.get).mockResolvedValueOnce({ reservations: [] })

    const reservations = useReservations()

    await reservations.fetchReservations({ date: '2026-03-12' })

    expect(api.apiClient.get).toHaveBeenCalledWith('/reservations?date=2026-03-12')
  })

  it('returns score and tier from customer lookup', async () => {
    vi.mocked(api.apiClient.get).mockResolvedValueOnce({
      found: true,
      reliability_score: 94,
      score_tier: 'reliable',
    })

    const reservations = useReservations()
    const result = await reservations.lookupCustomer('+33612345678')

    expect(result.reliability_score).toBe(94)
    expect(result.score_tier).toBe('reliable')
  })

  it('sets error state on API failure', async () => {
    vi.mocked(api.apiClient.post).mockRejectedValueOnce(new Error('boom'))

    const reservations = useReservations()

    await expect(
      reservations.createReservation({
        customer_name: 'Marc Dubois',
        phone: '+33612345678',
        scheduled_at: '2026-03-12T20:00',
      }),
    ).rejects.toThrow('boom')

    expect(reservations.errors.create.value).toBe('boom')
  })
})
