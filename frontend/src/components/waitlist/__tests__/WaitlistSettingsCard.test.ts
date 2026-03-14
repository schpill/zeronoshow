import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import WaitlistSettingsCard from '../WaitlistSettingsCard.vue'
import { useWaitlist } from '@/composables/useWaitlist'
import { ref } from 'vue'

vi.mock('@/composables/useWaitlist', () => ({
  useWaitlist: vi.fn(),
}))

describe('WaitlistSettingsCard', () => {
  const mockSettings = ref({
    waitlist_enabled: true,
    waitlist_notification_window_minutes: 15,
    public_registration_url: 'http://localhost/join/token',
  })

  beforeEach(() => {
    vi.mocked(useWaitlist).mockReturnValue({
      settings: mockSettings,
      fetchSettings: vi.fn(),
      updateSettings: vi.fn(),
      regenerateLink: vi.fn(),
    } /* eslint-disable-line @typescript-eslint/no-explicit-any */ as any)
  })

  it('renders settings values correctly', () => {
    const wrapper = mount(WaitlistSettingsCard)
    expect(wrapper.text()).toContain('15 minutes')
    expect(wrapper.find('input[type="text"]').element.getAttribute('value')).toBe(
      'http://localhost/join/token',
    )
  })

  it('triggers toggle when button clicked', async () => {
    const updateSettings = vi.fn()
    vi.mocked(useWaitlist).mockReturnValue({
      settings: mockSettings,
      fetchSettings: vi.fn(),
      updateSettings,
      regenerateLink: vi.fn(),
    } /* eslint-disable-line @typescript-eslint/no-explicit-any */ as any)

    const wrapper = mount(WaitlistSettingsCard)
    await wrapper.find('button.relative').trigger('click')

    expect(updateSettings).toHaveBeenCalledWith({ waitlist_enabled: false })
  })
})
