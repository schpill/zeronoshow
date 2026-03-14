import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import BookingStepDate from '../BookingStepDate.vue'

vi.mock('@/api/widget', () => ({
  getSlots: vi.fn(),
}))

import { getSlots } from '@/api/widget'

describe('BookingStepDate', () => {
  const defaultProps = {
    businessToken: 'tok-123',
    accentColour: '#6366f1',
    maxAdvanceDays: 30,
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders calendar for current month', () => {
    const wrapper = mount(BookingStepDate, { props: defaultProps })
    const today = new Date()
    const monthName = today.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })

    expect(wrapper.text()).toContain(monthName)
    expect(wrapper.text()).toContain('Choisissez une date')

    const headers = ['Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa', 'Di']
    for (const h of headers) {
      expect(wrapper.text()).toContain(h)
    }
  })

  it('shows time slots after date click (mock getSlots)', async () => {
    vi.mocked(getSlots).mockResolvedValue({ slots: ['10:00', '12:00', '19:30'] })

    const wrapper = mount(BookingStepDate, { props: defaultProps })

    const today = new Date()
    const dateStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

    const dayButtons = wrapper.findAll('button[type="button"]')
    const todayBtn = dayButtons.find((btn) => btn.text() === String(today.getDate()))
    expect(todayBtn).toBeDefined()

    await todayBtn!.trigger('click')
    await wrapper.vm.$nextTick()

    expect(getSlots).toHaveBeenCalledWith('tok-123', dateStr)
    expect(wrapper.text()).toContain('10:00')
    expect(wrapper.text()).toContain('12:00')
    expect(wrapper.text()).toContain('19:30')
  })

  it('shows "Aucun créneau" when slots empty', async () => {
    vi.mocked(getSlots).mockResolvedValue({ slots: [] })

    const wrapper = mount(BookingStepDate, { props: defaultProps })

    const today = new Date()
    const dayButtons = wrapper.findAll('button[type="button"]')
    const todayBtn = dayButtons.find((btn) => btn.text() === String(today.getDate()))

    await todayBtn!.trigger('click')
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('Aucun créneau disponible')
  })

  it('emits select when date and time both chosen', async () => {
    vi.mocked(getSlots).mockResolvedValue({ slots: ['10:00', '12:00'] })

    const wrapper = mount(BookingStepDate, { props: defaultProps })

    const today = new Date()
    const dateStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

    const dayButtons = wrapper.findAll('button[type="button"]')
    const todayBtn = dayButtons.find((btn) => btn.text() === String(today.getDate()))

    await todayBtn!.trigger('click')
    await wrapper.vm.$nextTick()

    const slotButtons = wrapper
      .findAll('button[type="button"]')
      .filter((btn) => btn.text() === '10:00')
    expect(slotButtons.length).toBeGreaterThan(0)

    await slotButtons[0]!.trigger('click')

    expect(wrapper.emitted('select')).toBeTruthy()
    expect(wrapper.emitted('select')![0]).toEqual([dateStr, '10:00'])
  })
})
