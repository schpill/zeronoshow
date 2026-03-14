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
          monthly_cap_cents: 500,
          auto_renew: true,
        },
      ],
    ])
  })

  it('emits a trimmed payload for whatsapp channel creation', async () => {
    const wrapper = mount(CreateLeoChannelForm, {
      props: {
        loading: false,
      },
    })

    const radios = wrapper.findAll('input[type="radio"]')
    await radios[1].setValue(true) // Switch to WhatsApp

    await wrapper.get('#leo-bot-name').setValue('Bot WA')
    await wrapper.get('#leo-chat-id').setValue(' +33612345678 ')
    await wrapper.get('#whatsapp-budget').setValue(10)

    const buttons = wrapper.findAll('button')
    const submitButton = buttons[buttons.length - 1]
    await submitButton!.trigger('click')

    expect(wrapper.emitted('created')).toEqual([
      [
        {
          channel: 'whatsapp',
          bot_name: 'Bot WA',
          external_identifier: '+33612345678',
          monthly_cap_cents: 1000,
          auto_renew: true,
        },
      ],
    ])
  })

  it('keeps non-enabled channel options disabled', () => {
    const wrapper = mount(CreateLeoChannelForm, {
      props: { loading: false },
    })

    const radios = wrapper.findAll('input[type="radio"]')
    expect(radios).toHaveLength(5)
    expect(radios[0]!.attributes('disabled')).toBeUndefined() // Telegram
    expect(radios[1]!.attributes('disabled')).toBeUndefined() // WhatsApp
    expect(radios[2]!.attributes('disabled')).toBeDefined() // SMS
  })
})
