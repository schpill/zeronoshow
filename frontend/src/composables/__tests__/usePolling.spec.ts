import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent, nextTick } from 'vue'
import { mount } from '@vue/test-utils'

import { usePolling } from '@/composables/usePolling'

describe('usePolling', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('calls the function immediately on mount and repeats at interval', async () => {
    const fn = vi.fn().mockResolvedValue(undefined)

    mount(
      defineComponent({
        setup() {
          usePolling(fn, 1_000)
          return () => null
        },
      }),
    )

    await nextTick()
    expect(fn).toHaveBeenCalledTimes(1)

    await vi.advanceTimersByTimeAsync(3_000)
    expect(fn).toHaveBeenCalledTimes(4)
  })

  it('stops polling on unmount', async () => {
    const fn = vi.fn().mockResolvedValue(undefined)

    const wrapper = mount(
      defineComponent({
        setup() {
          usePolling(fn, 1_000)
          return () => null
        },
      }),
    )

    await nextTick()
    await wrapper.unmount()
    await vi.advanceTimersByTimeAsync(3_000)

    expect(fn).toHaveBeenCalledTimes(1)
  })

  it('keeps polling when the function rejects', async () => {
    const fn = vi.fn().mockRejectedValue(new Error('boom'))

    mount(
      defineComponent({
        setup() {
          usePolling(fn, 1_000)
          return () => null
        },
      }),
    )

    await nextTick()
    await vi.advanceTimersByTimeAsync(2_000)

    expect(fn).toHaveBeenCalledTimes(3)
  })
})
