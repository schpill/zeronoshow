import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import PublicWaitlistView from '../PublicWaitlistView.vue'
import { useWaitlist } from '@/composables/useWaitlist'
import { useRoute } from 'vue-router'
import { ref } from 'vue'

vi.mock('@/composables/useWaitlist', () => ({
  useWaitlist: vi.fn(),
}))

vi.mock('vue-router', () => ({
  useRoute: vi.fn(),
}))

describe('PublicWaitlistView', () => {
  beforeEach(() => {
    vi.mocked(useRoute).mockReturnValue({
      params: { token: 'test-token' },
    } /* eslint-disable-line @typescript-eslint/no-explicit-any */ as any)

    vi.mocked(useWaitlist).mockReturnValue({
      getPublicWaitlistInfo: vi.fn().mockResolvedValue({
        business_name: 'Chez Luigi',
        slots_available: [{ date: '2026-03-30', times: ['19:30'] }],
      }),
      joinWaitlistPublic: vi.fn(),
    } /* eslint-disable-line @typescript-eslint/no-explicit-any */ as any)
  })

  it('renders business name and available slots', async () => {
    const wrapper = mount(PublicWaitlistView)

    // Wait for async setup
    await new Promise((resolve) => setTimeout(resolve, 0))

    expect(wrapper.text()).toContain('Chez Luigi')
    expect(wrapper.find('select').exists()).toBe(true)
  })

  it('submits form correctly', async () => {
    const joinWaitlistPublic = vi.fn().mockResolvedValue({})
    vi.mocked(useWaitlist).mockReturnValue({
      getPublicWaitlistInfo: vi.fn().mockResolvedValue({
        business_name: 'Chez Luigi',
        slots_available: [{ date: '2026-03-30', times: ['19:30'] }],
      }),
      joinWaitlistPublic,
    } /* eslint-disable-line @typescript-eslint/no-explicit-any */ as any)

    const wrapper = mount(PublicWaitlistView)
    await new Promise((resolve) => setTimeout(resolve, 0))

    await wrapper.find('input[type="text"]').setValue('Customer')
    await wrapper.find('input[type="tel"]').setValue('+33600000000')

    await wrapper.find('form').trigger('submit.prevent')
    expect(joinWaitlistPublic).toHaveBeenCalled()
  })
})
