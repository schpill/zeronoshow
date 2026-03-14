import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'

import ReviewSettingsCard from '@/components/reputation/ReviewSettingsCard.vue'

const updateSettings = vi.fn()
const fetchSettings = vi.fn()

const settings = ref({
  review_requests_enabled: false,
  review_platform: 'google',
  review_delay_hours: 2,
  google_place_id: null as string | null,
  tripadvisor_location_id: null as string | null,
})

vi.mock('@/composables/useReviewSettings', () => ({
  useReviewSettings: () => ({
    settings,
    requests: ref([]),
    stats: ref({ total_sent: 0, total_clicked: 0, click_rate_percent: 0 }),
    loading: ref(false),
    error: ref(null),
    fetchSettings,
    fetchRequests: vi.fn(),
    fetchStats: vi.fn(),
    updateSettings,
  }),
}))

describe('ReviewSettingsCard', () => {
  beforeEach(() => {
    updateSettings.mockReset().mockResolvedValue(undefined)
    fetchSettings.mockReset()
    settings.value = {
      review_requests_enabled: false,
      review_platform: 'google',
      review_delay_hours: 2,
      google_place_id: null,
      tripadvisor_location_id: null,
    }
  })

  it('renders toggle and platform radios', () => {
    const wrapper = mount(ReviewSettingsCard)

    expect(wrapper.find('[data-test="review-enabled"]').exists()).toBe(true)
    expect(wrapper.find('#review-platform-google').exists()).toBe(true)
    expect(wrapper.find('#review-platform-tripadvisor').exists()).toBe(true)
  })

  it('shows google field when google is selected', async () => {
    const wrapper = mount(ReviewSettingsCard)

    await wrapper.get('[data-test="review-enabled"]').setValue(true)

    expect(wrapper.find('#google-place-id').exists()).toBe(true)
    expect(wrapper.find('#tripadvisor-location-id').exists()).toBe(false)
  })

  it('shows tripadvisor field when tripadvisor is selected', async () => {
    const wrapper = mount(ReviewSettingsCard)

    await wrapper.get('#review-platform-tripadvisor').setValue(true)

    expect(wrapper.find('#tripadvisor-location-id').exists()).toBe(true)
  })

  it('shows validation error when enabled and no place id is provided', async () => {
    const wrapper = mount(ReviewSettingsCard)

    await wrapper.get('[data-test="review-enabled"]').setValue(true)
    await wrapper.get('[data-test="save-review-settings"]').trigger('click')

    expect(wrapper.text()).toContain('Place ID Google')
    expect(updateSettings).not.toHaveBeenCalled()
  })
})
