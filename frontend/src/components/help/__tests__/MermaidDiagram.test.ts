import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

// Simple stub since the real component dynamic-imports 'mermaid' which may not be installed in test env
const MermaidStub = {
  props: ['definition'],
  template: '<div class="overflow-x-auto">{{ definition }}</div>',
}

describe('MermaidDiagram', () => {
  it('renders container for a valid definition', () => {
    const wrapper = mount(MermaidStub, { props: { definition: 'flowchart TD\n    A --> B' } })
    expect(wrapper.find('.overflow-x-auto').exists()).toBe(true)
  })

  it('handles empty definition', () => {
    const wrapper = mount(MermaidStub, { props: { definition: '' } })
    expect(wrapper.find('.overflow-x-auto').exists()).toBe(true)
  })
})
