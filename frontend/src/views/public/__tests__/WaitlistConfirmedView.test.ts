import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import WaitlistConfirmedView from '../WaitlistConfirmedView.vue'
import { useRoute } from 'vue-router'

vi.mock('vue-router', () => ({
  useRoute: vi.fn(),
}))

describe('WaitlistConfirmedView', () => {
  it('renders confirmation message with name from query', () => {
    vi.mocked(useRoute).mockReturnValue({
      query: { name: 'John Doe', slot: '2026-03-30T19:30:00' },
    } /* eslint-disable-line @typescript-eslint/no-explicit-any */ as any)

    const wrapper = mount(WaitlistConfirmedView)
    expect(wrapper.text()).toContain('John Doe')
    expect(wrapper.text()).toContain('19:30')
  })
})
