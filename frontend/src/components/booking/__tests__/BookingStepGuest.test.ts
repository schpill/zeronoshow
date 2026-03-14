import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import BookingStepGuest from '../BookingStepGuest.vue'

describe('BookingStepGuest', () => {
  const defaultProps = {
    maxPartySize: 20,
    accentColour: '#6366f1',
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders all form fields', () => {
    const wrapper = mount(BookingStepGuest, { props: defaultProps })

    expect(wrapper.find('#guest-name').exists()).toBe(true)
    expect(wrapper.find('#guest-phone').exists()).toBe(true)
    expect(wrapper.find('#party-size').exists()).toBe(true)
    expect(wrapper.text()).toContain('Vos coordonnées')
    expect(wrapper.text()).toContain('Nom')
    expect(wrapper.text()).toContain('Téléphone')
    expect(wrapper.text()).toContain('Nombre de couverts')
  })

  it('validates required fields on submit', async () => {
    const wrapper = mount(BookingStepGuest, { props: defaultProps })

    const submitBtn = wrapper.find('button[type="submit"]')
    expect(submitBtn.attributes('disabled')).toBeDefined()

    await wrapper.find('#guest-name').setValue('Jean Dupont')
    await wrapper.find('#guest-phone').setValue('invalid')
    await wrapper.find('#party-size').setValue(2)
    await wrapper.vm.$nextTick()

    expect(submitBtn.attributes('disabled')).toBeDefined()
    expect(wrapper.emitted('submit')).toBeFalsy()
  })

  it('validates party_size within max', async () => {
    const wrapper = mount(BookingStepGuest, { props: defaultProps })

    await wrapper.find('#guest-name').setValue('Jean Dupont')
    await wrapper.find('#guest-phone').setValue('+33612345678')
    await wrapper.find('#party-size').setValue(25)
    await wrapper.vm.$nextTick()

    const submitBtn = wrapper.find('button[type="submit"]')
    expect(submitBtn.attributes('disabled')).toBeDefined()
  })

  it('emits submit with valid data', async () => {
    const wrapper = mount(BookingStepGuest, { props: defaultProps })

    await wrapper.find('#guest-name').setValue('Jean Dupont')
    await wrapper.find('#guest-phone').setValue('+33612345678')
    await wrapper.find('#party-size').setValue(4)
    await wrapper.vm.$nextTick()

    await wrapper.find('form').trigger('submit')

    expect(wrapper.emitted('submit')).toBeTruthy()
    expect(wrapper.emitted('submit')![0]).toEqual([{
      guest_name: 'Jean Dupont',
      guest_phone: '+33612345678',
      party_size: 4,
    }])
  })
})
