import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import WidgetStatsCard from '../WidgetStatsCard.vue'
import type { WidgetStatsResponse } from '@/api/widgetSettings'

describe('WidgetStatsCard', () => {
  const mockStats: WidgetStatsResponse = {
    widget_reservations_count: 150,
    widget_reservations_this_month: 25,
    widget_conversion_rate: 72,
  }

  const defaultProps = {
    stats: mockStats,
    loading: false,
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders reservation counts', () => {
    const wrapper = mount(WidgetStatsCard, { props: defaultProps })

    expect(wrapper.text()).toContain('150')
    expect(wrapper.text()).toContain('25')
    expect(wrapper.text()).toContain('Total')
    expect(wrapper.text()).toContain('Ce mois')
  })

  it('renders conversion rate as percentage', () => {
    const wrapper = mount(WidgetStatsCard, { props: defaultProps })

    expect(wrapper.text()).toContain('72%')
    expect(wrapper.text()).toContain('Conversion')
  })

  it('shows loading state', () => {
    const wrapper = mount(WidgetStatsCard, {
      props: { stats: null, loading: true },
    })

    expect(wrapper.text()).toContain('Chargement...')
    expect(wrapper.text()).not.toContain('Total')
  })
})
