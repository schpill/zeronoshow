import { beforeEach, describe, expect, it, vi } from 'vitest'

import * as api from '@/api/axios'
import { useSubscription } from '@/composables/useSubscription'

vi.mock('@/api/axios', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

describe('useSubscription', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('fetches the subscription snapshot and computes remaining days', async () => {
    vi.mocked(api.apiClient.get).mockResolvedValueOnce({
      subscription_status: 'trial',
      trial_ends_at: new Date(Date.now() + 3 * 86_400_000).toISOString(),
      sms_cost_this_month: 1.23,
    })

    const subscription = useSubscription()
    await subscription.fetchSubscription()

    expect(api.apiClient.get).toHaveBeenCalledWith('/subscription')
    expect(subscription.daysUntilTrialEnd.value).toBe(3)
  })
})
