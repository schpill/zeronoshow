import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import SmsLogTable from '@/components/SmsLogTable.vue'

describe('SmsLogTable', () => {
  it('renders rows and formatted costs', () => {
    const wrapper = mount(SmsLogTable, {
      props: {
        logs: [
          {
            id: 'sms-1',
            type: 'reminder',
            status: 'delivered',
            phone: '+33612345678',
            body: 'Bonjour',
            cost_eur: 0.12,
            sent_at: '2026-03-13T12:00:00Z',
            delivered_at: '2026-03-13T12:01:00Z',
          },
        ],
      },
    })

    expect(wrapper.text()).toContain('reminder')
    expect(wrapper.text()).toContain('0,12')
    expect(wrapper.text()).toContain('2026-03-13T12:01:00Z')
    expect(wrapper.html()).toContain('bg-emerald-100')
  })

  it('renders an empty state', () => {
    const wrapper = mount(SmsLogTable, {
      props: { logs: [] },
    })

    expect(wrapper.text()).toContain('Aucun SMS')
  })
})
