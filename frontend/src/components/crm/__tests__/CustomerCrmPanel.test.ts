import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'

import CustomerCrmPanel from '@/components/crm/CustomerCrmPanel.vue'

const updateCustomerCrm = vi.fn()

vi.mock('@/composables/useCustomerCrm', () => ({
  useCustomerCrm: () => ({
    loading: ref(false),
    error: ref(null),
    updateCustomerCrm,
  }),
}))

describe('CustomerCrmPanel', () => {
  const customer = {
    id: 'cust-1',
    phone: '+33612345678',
    reliability_score: 91,
    score_tier: 'reliable',
    reservations_count: 4,
    shows_count: 4,
    no_shows_count: 0,
    opted_out: false,
    notes: 'Aime les tables calmes',
    is_vip: false,
    is_blacklisted: false,
    birthday_month: 3,
    birthday_day: 14,
    preferred_table_notes: 'Fenêtre',
  } as const

  beforeEach(() => {
    updateCustomerCrm.mockReset().mockResolvedValue({
      ...customer,
      is_vip: true,
    })
  })

  it('renders notes textarea with existing value', () => {
    const wrapper = mount(CustomerCrmPanel, {
      props: { customer, open: true },
    })

    expect((wrapper.get('#crm-notes').element as HTMLTextAreaElement).value).toBe(
      'Aime les tables calmes',
    )
  })

  it('saves vip toggle and shows saved message', async () => {
    const wrapper = mount(CustomerCrmPanel, {
      props: { customer, open: true },
    })

    await wrapper.get('[data-test="crm-vip-toggle"]').setValue(true)
    await wrapper.get('[data-test="crm-save"]').trigger('click')

    expect(updateCustomerCrm).toHaveBeenCalledWith(
      'cust-1',
      expect.objectContaining({ is_vip: true }),
    )
    expect(wrapper.text()).toContain('Sauvegardé')
  })

  it('saves blacklist toggle through the api', async () => {
    const wrapper = mount(CustomerCrmPanel, {
      props: { customer, open: true },
    })

    await wrapper.get('[data-test="crm-blacklist-toggle"]').setValue(true)
    await wrapper.get('[data-test="crm-save"]').trigger('click')

    expect(updateCustomerCrm).toHaveBeenCalledWith(
      'cust-1',
      expect.objectContaining({ is_blacklisted: true }),
    )
  })
})
