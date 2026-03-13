import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import LeoChannelTypeBadge from '@/components/leo/LeoChannelTypeBadge.vue'

describe('LeoChannelTypeBadge', () => {
  it('renders the active telegram badge', () => {
    const wrapper = mount(LeoChannelTypeBadge, {
      props: {
        type: 'telegram',
      },
    })

    expect(wrapper.text()).toContain('Telegram')
    expect(wrapper.text()).toContain('Actif')
    expect(wrapper.classes()).toContain('border-blue-200')
  })

  it('renders upcoming channels as bientot', () => {
    const wrapper = mount(LeoChannelTypeBadge, {
      props: {
        type: 'whatsapp',
      },
    })

    expect(wrapper.text()).toContain('WhatsApp')
    expect(wrapper.text()).toContain('Bientôt')
  })
})
