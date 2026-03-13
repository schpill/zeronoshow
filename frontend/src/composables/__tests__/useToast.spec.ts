import { beforeEach, describe, expect, it, vi } from 'vitest'

import { resetToastsForTests, useToast } from '@/composables/useToast'

describe('useToast', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    resetToastsForTests()
  })

  it('queues multiple toasts and auto dismisses them after four seconds', () => {
    const toast = useToast()

    toast.success('Saved')
    toast.warning('Retry later')

    expect(toast.toasts.value).toHaveLength(2)

    vi.advanceTimersByTime(4000)

    expect(toast.toasts.value).toHaveLength(0)
  })

  it('keeps persistent toasts until they are dismissed manually', () => {
    const toast = useToast()

    const created = toast.error('Service unavailable', { duration: 0 })

    vi.advanceTimersByTime(10_000)

    expect(toast.toasts.value).toHaveLength(1)

    toast.dismiss(created.id)

    expect(toast.toasts.value).toHaveLength(0)
  })
})
