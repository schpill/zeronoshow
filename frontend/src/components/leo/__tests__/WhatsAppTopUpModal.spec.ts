import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import WhatsAppTopUpModal from '@/components/leo/WhatsAppTopUpModal.vue'

describe('WhatsAppTopUpModal', () => {
  const defaultProps = {
    modelValue: true,
    loading: false,
  }

  it('renders presets correctly', () => {
    const wrapper = mount(WhatsAppTopUpModal, { props: defaultProps })
    const presets = wrapper.findAll('button.rounded-2xl.border.py-3')
    expect(presets).toHaveLength(5)
    expect(presets[0]!.text()).toBe('2 €')
  })

  it('selects a preset', async () => {
    const wrapper = mount(WhatsAppTopUpModal, { props: defaultProps })
    const presets = wrapper.findAll('button.rounded-2xl.border.py-3')
    await presets[2]!.trigger('click') // 10€

    expect(wrapper.text()).toContain('10,00 €')
  })

  it('handles custom amount input', async () => {
    const wrapper = mount(WhatsAppTopUpModal, { props: defaultProps })
    const input = wrapper.find('input[type="number"]')
    await input.setValue(15)

    expect(wrapper.text()).toContain('15,00 €')
    // Preset should be deselected (not having emerald class)
    const presets = wrapper.findAll('button.rounded-2xl.border.py-3')
    expect(presets[1]!.classes()).not.toContain('bg-emerald-50')
  })

  it('emits submit event with correct cents', async () => {
    const wrapper = mount(WhatsAppTopUpModal, { props: defaultProps })
    await wrapper.find('input[type="number"]').setValue(25)
    await wrapper.find('button.bg-emerald-600').trigger('click')

    expect(wrapper.emitted('submit')).toEqual([[2500]])
  })

  it('emits close event', async () => {
    const wrapper = mount(WhatsAppTopUpModal, { props: defaultProps })
    await wrapper.find('button.text-slate-400').trigger('click')
    expect(wrapper.emitted('update:modelValue')).toEqual([[false]])
  })

  it('disables submit when loading', () => {
    const wrapper = mount(WhatsAppTopUpModal, {
      props: { ...defaultProps, loading: true },
    })
    expect(wrapper.find('button.bg-emerald-600').attributes('disabled')).toBeDefined()
  })
})
