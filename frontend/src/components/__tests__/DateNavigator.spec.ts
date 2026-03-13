import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import DateNavigator from '@/components/DateNavigator.vue'

describe('DateNavigator', () => {
  it('emits previous next and today dates', async () => {
    const wrapper = mount(DateNavigator, {
      props: {
        modelValue: '2026-03-13',
      },
    })

    await wrapper.get('button[aria-label="Jour précédent"]').trigger('click')
    await wrapper.get('button[aria-label="Jour suivant"]').trigger('click')

    const emitted = wrapper.emitted('update:modelValue') ?? []

    expect(emitted[0]).toEqual(['2026-03-12'])
    expect(emitted[1]).toEqual(['2026-03-14'])
  })
})
