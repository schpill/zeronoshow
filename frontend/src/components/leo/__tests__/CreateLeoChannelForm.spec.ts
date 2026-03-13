import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import CreateLeoChannelForm from '@/components/leo/CreateLeoChannelForm.vue'

describe('CreateLeoChannelForm', () => {
  it('validates required fields before emitting creation', async () => {
    const wrapper = mount(CreateLeoChannelForm, {
      props: {
        loading: false,
      },
    })

    await wrapper.get('#leo-bot-name').setValue('   ')
    await wrapper.get('#leo-chat-id').setValue('')
    const buttons = wrapper.findAll('button')
    const submitButton = buttons[buttons.length - 1]

    expect(submitButton).toBeDefined()

    await submitButton!.trigger('click')

    expect(wrapper.text()).toContain('Le nom du bot est obligatoire.')
    expect(wrapper.emitted('created')).toBeUndefined()
  })

  it('uses a generic channel identifier validation message', async () => {
    const wrapper = mount(CreateLeoChannelForm, {
      props: {
        loading: false,
      },
    })

    await wrapper.get('#leo-bot-name').setValue('Léo')
    await wrapper.get('#leo-chat-id').setValue('')
    const buttons = wrapper.findAll('button')
    const submitButton = buttons[buttons.length - 1]

    expect(submitButton).toBeDefined()

    await submitButton!.trigger('click')

    expect(wrapper.text()).toContain('Identifiant du canal est obligatoire.')
    expect(wrapper.text()).not.toContain('Chat ID Telegram est obligatoire.')
  })

  it('emits a trimmed payload for telegram channel creation', async () => {
    const wrapper = mount(CreateLeoChannelForm, {
      props: {
        loading: false,
      },
    })

    await wrapper.get('#leo-bot-name').setValue('  Léo Bastille  ')
    await wrapper.get('#leo-chat-id').setValue(' 123456789 ')
    const buttons = wrapper.findAll('button')
    const submitButton = buttons[buttons.length - 1]

    expect(submitButton).toBeDefined()

    await submitButton!.trigger('click')

    expect(wrapper.emitted('created')).toEqual([
      [
        {
          channel: 'telegram',
          bot_name: 'Léo Bastille',
          external_identifier: '123456789',
        },
      ],
    ])
  })

  it('keeps non-telegram channel options disabled', () => {
    const wrapper = mount(CreateLeoChannelForm, {
      props: {
        loading: false,
      },
    })

    const radios = wrapper.findAll('input[type="radio"]')

    expect(radios).toHaveLength(5)
    expect(radios[0]!.attributes('disabled')).toBeUndefined()
    expect(radios[1]!.attributes('disabled')).toBeDefined()
    expect(wrapper.text()).toContain('Comment obtenir votre Chat ID Telegram')
  })
})
