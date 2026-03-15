import { describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'

vi.mock('@/components/help/MermaidDiagram.vue', () => ({
  default: { name: 'MermaidDiagram', props: ['definition'], template: '<div />' },
}))

import HelpScoringView from '@/views/help/HelpScoringView.vue'

describe('HelpScoringView', () => {
  it('renders all 3 score tiers', () => {
    const wrapper = mount(HelpScoringView)

    expect(wrapper.text()).toContain('Fiable')
    expect(wrapper.text()).toContain('Moyen')
    expect(wrapper.text()).toContain('À risque')
  })

  it('renders the mermaid diagram placeholder', () => {
    const wrapper = mount(HelpScoringView)
    expect(wrapper.find('div').exists()).toBe(true)
  })

  it('explains the formula', () => {
    const wrapper = mount(HelpScoringView)
    expect(wrapper.text()).toContain('présences / total_réservations')
  })
})
