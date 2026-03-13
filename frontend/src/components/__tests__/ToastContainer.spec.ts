import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it } from 'vitest'

import ToastContainer from '@/components/ToastContainer.vue'
import { resetToastsForTests, useToast } from '@/composables/useToast'

describe('ToastContainer', () => {
  beforeEach(() => {
    resetToastsForTests()
  })

  it('renders queued toasts with alert semantics', () => {
    const toast = useToast()
    toast.success('Reservation created')

    const wrapper = mount(ToastContainer)

    expect(wrapper.get('[role="alert"]').text()).toContain('Reservation created')
  })

  it('dismisses the latest toast when escape is pressed', async () => {
    const toast = useToast()
    toast.success('First')
    toast.error('Second', { duration: 0 })

    const wrapper = mount(ToastContainer)

    await wrapper.trigger('keydown', { key: 'Escape' })

    expect(wrapper.text()).toContain('First')
    expect(wrapper.text()).not.toContain('Second')
  })

  it('dismisses a toast from the close button', async () => {
    const toast = useToast()
    toast.warning('Watch out', { duration: 0 })

    const wrapper = mount(ToastContainer)

    await wrapper.get('button').trigger('click')

    expect(wrapper.text()).not.toContain('Watch out')
  })
})
