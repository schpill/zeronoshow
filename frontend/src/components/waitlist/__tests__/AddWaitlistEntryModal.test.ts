import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import AddWaitlistEntryModal from '../AddWaitlistEntryModal.vue'

describe('AddWaitlistEntryModal', () => {
  it('emits close when background is clicked', async () => {
    const wrapper = mount(AddWaitlistEntryModal, {
      props: { show: true, loading: false },
    })
    await wrapper.find('.bg-gray-500').trigger('click')
    expect(wrapper.emitted()).toHaveProperty('close')
  })

  it('emits submit with form data', async () => {
    const wrapper = mount(AddWaitlistEntryModal, {
      props: { show: true, loading: false },
    })

    await wrapper.find('input[type="text"]').setValue('John Doe')
    await wrapper.find('input[type="tel"]').setValue('+33612345678')

    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.emitted('submit')).toBeTruthy()
    const payload = wrapper.emitted(
      'submit',
    )?.[0][0] /* eslint-disable-line @typescript-eslint/no-explicit-any */ as any
    expect(payload.client_name).toBe('John Doe')
  })
})
