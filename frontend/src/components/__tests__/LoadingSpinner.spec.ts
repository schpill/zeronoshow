import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import LoadingSpinner from '@/components/LoadingSpinner.vue'

describe('LoadingSpinner', () => {
  it('renders an accessible status indicator', () => {
    const wrapper = mount(LoadingSpinner, {
      props: {
        size: 'lg',
        label: 'Chargement des reservations',
      },
    })

    expect(wrapper.get('[role="status"]').attributes('aria-label')).toBe(
      'Chargement des reservations',
    )
    expect(wrapper.find('svg').classes()).toContain('animate-spin')
  })

  it.each([
    ['sm', 'h-4'],
    ['md', 'h-6'],
    ['lg', 'h-10'],
  ] as const)('renders the %s size variant', (size, expectedClass) => {
    const wrapper = mount(LoadingSpinner, {
      props: {
        size,
      },
    })

    expect(wrapper.find('svg').classes()).toContain(expectedClass)
  })
})
