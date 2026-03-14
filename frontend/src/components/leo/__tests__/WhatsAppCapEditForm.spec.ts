import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import WhatsAppCapEditForm from '@/components/leo/WhatsAppCapEditForm.vue'

describe('WhatsAppCapEditForm', () => {
  const defaultProps = {
    initialCapCents: 500,
    initialAutoRenew: true,
    loading: false,
  }

  it('renders initial values correctly', () => {
    const wrapper = mount(WhatsAppCapEditForm, { props: defaultProps })
    const input = wrapper.find('input[type="number"]')
    expect((input.element as HTMLInputElement).value).toBe('5')
    expect((wrapper.find('input[type="checkbox"]').element as HTMLInputElement).checked).toBe(true)
  })

  it('validates minimum amount', async () => {
    const wrapper = mount(WhatsAppCapEditForm, { props: defaultProps })
    const input = wrapper.find('input[type="number"]')
    await input.setValue(0.5)
    await wrapper.find('button.bg-slate-900').trigger('click')

    expect(wrapper.text()).toContain('budget minimum est de 1 €')
    expect(wrapper.emitted('save')).toBeUndefined()
  })

  it('validates maximum amount', async () => {
    const wrapper = mount(WhatsAppCapEditForm, { props: defaultProps })
    const input = wrapper.find('input[type="number"]')
    await input.setValue(101)
    await wrapper.find('button.bg-slate-900').trigger('click')

    expect(wrapper.text()).toContain('budget maximum est de 100 €')
  })

  it('emits save event with correct cents', async () => {
    const wrapper = mount(WhatsAppCapEditForm, { props: defaultProps })
    await wrapper.find('input[type="number"]').setValue(12.5)
    await wrapper.find('input[type="checkbox"]').setValue(false)
    await wrapper.find('button.bg-slate-900').trigger('click')

    expect(wrapper.emitted('save')).toEqual([[1250, false]])
  })

  it('emits cancel event', async () => {
    const wrapper = mount(WhatsAppCapEditForm, { props: defaultProps })
    await wrapper.find('button.text-slate-500').trigger('click')
    expect(wrapper.emitted('cancel')).toBeTruthy()
  })

  it('shows loading spinner', () => {
    const wrapper = mount(WhatsAppCapEditForm, {
      props: { ...defaultProps, loading: true },
    })
    expect(wrapper.findComponent({ name: 'LoadingSpinner' }).exists()).toBe(true)
    expect(wrapper.find('button.bg-slate-900').attributes('disabled')).toBeDefined()
  })
})
