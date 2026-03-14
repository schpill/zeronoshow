import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import ReviewStatsBar from '@/components/reputation/ReviewStatsBar.vue'

describe('ReviewStatsBar', () => {
  it('renders sent count, clicked count, and click rate percentage', () => {
    const wrapper = mount(ReviewStatsBar, {
      props: {
        stats: {
          total_sent: 12,
          total_clicked: 3,
          click_rate_percent: 25,
        },
      },
    })

    expect(wrapper.text()).toContain('12')
    expect(wrapper.text()).toContain('3')
    expect(wrapper.text()).toContain('25%')
  })
})
