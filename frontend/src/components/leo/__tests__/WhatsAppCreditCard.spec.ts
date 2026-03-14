import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import WhatsAppCreditCard from '@/components/leo/WhatsAppCreditCard.vue'

describe('WhatsAppCreditCard', () => {
  const mockStatus = {
    balance_cents: 400,
    balance_euros: 4.0,
    monthly_cap_cents: 1000,
    monthly_cap_euros: 10.0,
    auto_renew: true,
    is_channel_active: true,
    low_balance_warning: false,
  }

  it('renders balance and cap correctly', () => {
    const wrapper = mount(WhatsAppCreditCard, {
      props: { status: mockStatus },
    })

    expect(wrapper.text()).toContain('4,00\u00A0€')
    expect(wrapper.text()).toContain('/ 10,00\u00A0€')
    expect(wrapper.find('[role="progressbar"]').attributes('style')).toContain('width: 40%')
  })

  it('shows low balance warning', () => {
    const wrapper = mount(WhatsAppCreditCard, {
      props: { status: { ...mockStatus, low_balance_warning: true } },
    })

    expect(wrapper.text()).toContain('solde est faible')
  })

  it('emits events on button clicks', async () => {
    const wrapper = mount(WhatsAppCreditCard, {
      props: { status: mockStatus },
    })

    await wrapper.find('button:nth-of-type(2)').trigger('click')
    expect(wrapper.emitted('topup')).toBeTruthy()

    await wrapper.find('button:nth-of-type(1)').trigger('click')
    expect(wrapper.emitted('edit-cap')).toBeTruthy()
  })
})
