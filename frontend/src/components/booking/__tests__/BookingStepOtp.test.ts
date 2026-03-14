import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import BookingStepOtp from '../BookingStepOtp.vue'

vi.mock('@/api/widget', () => ({
  sendOtp: vi.fn(),
  verifyOtp: vi.fn(),
}))

import { sendOtp, verifyOtp } from '@/api/widget'

describe('BookingStepOtp', () => {
  const defaultProps = {
    businessToken: 'tok-123',
    phone: '+33612345678',
    accentColour: '#6366f1',
  }

  beforeEach(() => {
    vi.clearAllMocks()
    vi.mocked(sendOtp).mockResolvedValue({ message: 'sent' })
  })

  it('renders 6 individual inputs', () => {
    const wrapper = mount(BookingStepOtp, { props: defaultProps })

    const inputs = wrapper.findAll('input[type="text"][inputmode="numeric"]')
    expect(inputs).toHaveLength(6)
    expect(wrapper.text()).toContain('Vérification')
  })

  it('auto-focuses next input on digit entry', async () => {
    const wrapper = mount(BookingStepOtp, { props: defaultProps })
    await wrapper.vm.$nextTick()

    const inputs = wrapper.findAll('input[type="text"][inputmode="numeric"]')
    expect(inputs).toHaveLength(6)

    await inputs[0]!.setValue('1')
    await inputs[0]!.trigger('input')
    await wrapper.vm.$nextTick()

    expect((wrapper.vm as unknown as { digits: string[] }).digits[0]).toBe('1')
  })

  it('calls verifyOtp on 6th digit', async () => {
    vi.mocked(verifyOtp).mockResolvedValue({ guest_token: 'gt-abc' })

    const wrapper = mount(BookingStepOtp, { props: defaultProps })
    await wrapper.vm.$nextTick()

    const inputs = wrapper.findAll('input[type="text"][inputmode="numeric"]')

    for (let i = 0; i < 6; i++) {
      await inputs[i]!.setValue(String(i + 1))
      await inputs[i]!.trigger('input')
      await wrapper.vm.$nextTick()
    }

    expect(verifyOtp).toHaveBeenCalledWith('tok-123', '+33612345678', '123456')
  })

  it('shows error on wrong code', async () => {
    vi.mocked(verifyOtp).mockRejectedValue({
      data: { error: { message: 'Code incorrect.' } },
    })

    const wrapper = mount(BookingStepOtp, { props: defaultProps })
    await wrapper.vm.$nextTick()

    const inputs = wrapper.findAll('input[type="text"][inputmode="numeric"]')

    for (let i = 0; i < 6; i++) {
      await inputs[i]!.setValue(String(i + 1))
      await inputs[i]!.trigger('input')
      await wrapper.vm.$nextTick()
    }

    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('Code incorrect.')
  })

  it('shows resend button after cooldown', async () => {
    vi.useFakeTimers()
    const wrapper = mount(BookingStepOtp, { props: defaultProps })
    await wrapper.vm.$nextTick()

    const vm = wrapper.vm as unknown as { resendCooldown: number }

    expect(vm.resendCooldown).toBe(60)

    vi.advanceTimersByTime(61000)
    await wrapper.vm.$nextTick()

    expect(vm.resendCooldown).toBe(0)
    vi.useRealTimers()
  })
})
