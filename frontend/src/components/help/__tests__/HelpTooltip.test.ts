import { describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'

import HelpTooltip from '@/components/help/HelpTooltip.vue'

describe('HelpTooltip', () => {
  it('renders question mark icon by default', () => {
    const wrapper = mount(HelpTooltip, {
      props: { content: 'Test tooltip content' },
    })

    expect(wrapper.text()).toContain('?')
  })

  it('tooltip is hidden by default', () => {
    const wrapper = mount(HelpTooltip, {
      props: { content: 'Test tooltip content' },
    })

    expect(wrapper.find('[role="tooltip"]').exists()).toBe(false)
  })

  it('shows content on click', async () => {
    const wrapper = mount(HelpTooltip, {
      props: { content: 'Test tooltip content' },
    })

    await wrapper.find('button').trigger('click')
    await nextTick()

    expect(wrapper.find('[role="tooltip"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Test tooltip content')
  })

  it('hides content on second click', async () => {
    const wrapper = mount(HelpTooltip, {
      props: { content: 'Test tooltip content' },
    })

    await wrapper.find('button').trigger('click')
    await nextTick()
    await wrapper.find('button').trigger('click')
    await nextTick()

    expect(wrapper.find('[role="tooltip"]').exists()).toBe(false)
  })

  it('applies correct aria attributes', () => {
    const wrapper = mount(HelpTooltip, {
      props: { content: 'Test tooltip content' },
    })

    const button = wrapper.find('button')
    const describedBy = button.attributes('aria-describedby')
    expect(describedBy).toBeTruthy()

    // When tooltip is visible, it should have matching id
    const tooltip = wrapper.find('[role="tooltip"]')
    // Not visible yet
    expect(tooltip.exists()).toBe(false)
  })
})
