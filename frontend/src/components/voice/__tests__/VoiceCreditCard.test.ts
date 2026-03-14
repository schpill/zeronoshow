import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import VoiceCreditCard from '@/components/voice/VoiceCreditCard.vue'

describe('VoiceCreditCard', () => {
  it('renders balance and cap and emits actions', async () => {
    const wrapper = mount(VoiceCreditCard, {
      props: {
        status: {
          balance_cents: 500,
          balance_euros: 5,
          monthly_cap_cents: 1000,
          monthly_cap_euros: 10,
          auto_renew: true,
          auto_call_enabled: true,
          auto_call_score_threshold: 40,
          auto_call_min_party_size: 6,
          retry_count: 2,
          retry_delay_minutes: 10,
          is_channel_active: true,
          low_balance_warning: false,
        },
      },
    })

    expect(wrapper.text()).toContain('5,00')
    expect(wrapper.text()).toContain('10,00')

    await wrapper.get('button.rounded-2xl').trigger('click')
    await wrapper.get('button.text-sm').trigger('click')

    expect(wrapper.emitted('topup')).toHaveLength(1)
    expect(wrapper.emitted('edit-cap')).toHaveLength(1)
  })

  it('shows low balance warning', () => {
    const wrapper = mount(VoiceCreditCard, {
      props: {
        status: {
          balance_cents: 50,
          balance_euros: 0.5,
          monthly_cap_cents: 1000,
          monthly_cap_euros: 10,
          auto_renew: false,
          auto_call_enabled: false,
          auto_call_score_threshold: null,
          auto_call_min_party_size: null,
          retry_count: 2,
          retry_delay_minutes: 10,
          is_channel_active: false,
          low_balance_warning: true,
        },
      },
    })

    expect(wrapper.text()).toContain('Votre solde est faible')
  })
})
