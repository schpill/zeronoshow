import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'

import ReputationView from '@/views/ReputationView.vue'

const fetchSettings = vi.fn()
const fetchRequests = vi.fn()
const fetchStats = vi.fn()

const settings = ref({
  review_requests_enabled: true,
  review_platform: 'google',
  review_delay_hours: 2,
  google_place_id: 'ChIJ123456789',
  tripadvisor_location_id: null as string | null,
})
const requests = ref([
  {
    id: 'req-1',
    reservation_id: 'res-1',
    customer_name: 'Marc',
    platform: 'google',
    status: 'sent',
    short_url: 'https://zeronoshow.test/r/abc',
    sent_at: '2026-03-14T10:00:00Z',
    clicked_at: null,
    expires_at: '2026-04-14T10:00:00Z',
  },
])
const stats = ref({
  total_sent: 1,
  total_clicked: 0,
  click_rate_percent: 0,
})

vi.mock('@/composables/useReviewSettings', () => ({
  useReviewSettings: () => ({
    settings,
    requests,
    stats,
    loading: ref(false),
    error: ref(null),
    fetchSettings,
    fetchRequests,
    fetchStats,
    updateSettings: vi.fn(),
  }),
}))

describe('ReputationView', () => {
  beforeEach(() => {
    fetchSettings.mockReset().mockResolvedValue(undefined)
    fetchRequests.mockReset().mockResolvedValue(undefined)
    fetchStats.mockReset().mockResolvedValue(undefined)
  })

  it('loads settings, stats, and request history on mount', async () => {
    const wrapper = mount(ReputationView, {
      global: {
        stubs: {
          AppLayout: { template: '<div><slot /></div>' },
        },
      },
    })

    await Promise.resolve()

    expect(fetchSettings).toHaveBeenCalled()
    expect(fetchRequests).toHaveBeenCalled()
    expect(fetchStats).toHaveBeenCalled()
    expect(wrapper.text()).toContain('Paramètres avis')
    expect(wrapper.text()).toContain('Historique des demandes')
  })
})
