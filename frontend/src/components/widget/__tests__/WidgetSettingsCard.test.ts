import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import WidgetSettingsCard from '../WidgetSettingsCard.vue'
import type { WidgetSettingsRecord } from '@/api/widgetSettings'

describe('WidgetSettingsCard', () => {
  const mockSettings: WidgetSettingsRecord = {
    id: 'ws-1',
    business_id: 'biz-1',
    logo_url: null,
    accent_colour: '#6366f1',
    max_party_size: 8,
    advance_booking_days: 30,
    same_day_cutoff_minutes: 120,
    is_enabled: true,
    embed_url: 'http://localhost/widget/embed/tok-1',
    booking_url: 'http://localhost/widget/tok-1',
    created_at: null,
    updated_at: null,
  }

  const defaultProps = {
    settings: mockSettings,
    loading: false,
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders current settings', () => {
    const wrapper = mount(WidgetSettingsCard, { props: defaultProps })

    expect(wrapper.text()).toContain('Paramètres')
    expect(wrapper.text()).toContain('#6366f1')
    expect(wrapper.text()).toContain('8')
    expect(wrapper.text()).toContain('30')
    expect(wrapper.text()).toContain('120 min')
  })

  it('emits edit on modifier button click', async () => {
    const wrapper = mount(WidgetSettingsCard, { props: defaultProps })

    const editBtn = wrapper.findAll('button').find((btn) => btn.text().includes('Modifier'))
    expect(editBtn).toBeDefined()

    await editBtn!.trigger('click')

    expect(wrapper.emitted('edit')).toBeTruthy()
  })

  it('shows enabled/disabled status', () => {
    const enabledWrapper = mount(WidgetSettingsCard, {
      props: { ...defaultProps, settings: { ...mockSettings, is_enabled: true } },
    })
    expect(enabledWrapper.text()).toContain('Activé')

    const disabledWrapper = mount(WidgetSettingsCard, {
      props: { ...defaultProps, settings: { ...mockSettings, is_enabled: false } },
    })
    expect(disabledWrapper.text()).toContain('Désactivé')
  })
})
