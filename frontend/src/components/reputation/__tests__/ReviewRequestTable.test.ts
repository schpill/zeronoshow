import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import ReviewRequestTable from '@/components/reputation/ReviewRequestTable.vue'

describe('ReviewRequestTable', () => {
  it('renders rows with status badges and platform labels', () => {
    const wrapper = mount(ReviewRequestTable, {
      props: {
        requests: [
          {
            id: 'req-1',
            reservation_id: 'res-1',
            customer_name: 'Marc',
            platform: 'google',
            status: 'clicked',
            short_url: 'https://zeronoshow.test/r/abc',
            sent_at: '2026-03-14T10:00:00Z',
            clicked_at: '2026-03-14T10:30:00Z',
            expires_at: '2026-04-14T10:00:00Z',
          },
        ],
      },
    })

    expect(wrapper.text()).toContain('Marc')
    expect(wrapper.text()).toContain('Google')
    expect(wrapper.text()).toContain('Cliqué')
  })

  it('shows an empty state when there are no requests', () => {
    const wrapper = mount(ReviewRequestTable, {
      props: { requests: [] },
    })

    expect(wrapper.text()).toContain('Aucune demande envoyée')
  })
})
