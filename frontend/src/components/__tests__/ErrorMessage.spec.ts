import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import ErrorMessage from '@/components/ErrorMessage.vue'

describe('ErrorMessage', () => {
  it('renders the message and emits retry when requested', async () => {
    const wrapper = mount(ErrorMessage, {
      props: {
        title: 'Erreur de chargement',
        message: 'Impossible de recuperer les donnees.',
      },
    })

    expect(wrapper.text()).toContain('Erreur de chargement')
    expect(wrapper.text()).toContain('Impossible de recuperer les donnees.')

    await wrapper.get('button').trigger('click')

    expect(wrapper.emitted('retry')).toHaveLength(1)
  })
})
