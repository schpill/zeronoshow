import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import LeoChannelCard from '@/components/leo/LeoChannelCard.vue'

const baseChannel = {
  id: 'leo-1',
  business_id: 'biz-1',
  channel: 'telegram' as const,
  bot_name: 'Léo République',
  is_active: true,
  external_identifier_masked: '***6789',
}

describe('LeoChannelCard', () => {
  it('renders the channel details and emits toggle with the inverse active state', async () => {
    const wrapper = mount(LeoChannelCard, {
      props: {
        channel: baseChannel,
        busy: false,
      },
    })

    expect(wrapper.text()).toContain('Léo République')
    expect(wrapper.text()).toContain('***6789')
    expect(wrapper.text()).toContain('Canal actif')

    await wrapper.get('button').trigger('click')

    expect(wrapper.emitted('toggle')).toEqual([[false]])
  })

  it('shows the delete confirmation and emits delete when confirmed', async () => {
    const wrapper = mount(LeoChannelCard, {
      props: {
        channel: { ...baseChannel, is_active: false },
        busy: false,
      },
    })

    await wrapper.findAll('button')[1]!.trigger('click')

    expect(wrapper.text()).toContain('Cette action supprimera votre canal Léo actuel.')

    await wrapper.findAll('button')[2]!.trigger('click')

    expect(wrapper.emitted('delete')).toEqual([[]])
  })
})
