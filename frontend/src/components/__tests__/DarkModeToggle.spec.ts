import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import DarkModeToggle from '@/components/DarkModeToggle.vue'
import { initializeTheme } from '@/utils/theme'

describe('DarkModeToggle', () => {
  beforeEach(() => {
    document.documentElement.className = ''
    vi.stubGlobal('localStorage', {
      getItem: vi.fn(() => 'dark'),
      setItem: vi.fn(),
    })
  })

  it('initializes and toggles the html dark class', async () => {
    initializeTheme()

    expect(document.documentElement.classList.contains('dark')).toBe(true)

    const wrapper = mount(DarkModeToggle)
    await wrapper.trigger('click')

    expect(document.documentElement.classList.contains('dark')).toBe(false)
    expect(wrapper.findAll('svg')).toHaveLength(1)
  })
})
