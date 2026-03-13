import { ref } from 'vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { useLeoChannels } from '@/composables/useLeoChannels'

const useLeoMock = vi.fn()

vi.mock('@/composables/useLeo', () => ({
  useLeo: () => useLeoMock(),
}))

describe('useLeoChannels', () => {
  beforeEach(() => {
    useLeoMock.mockReset()
  })

  it('exposes a one-item channels array when a leo channel exists', () => {
    const channel = ref({
      id: 'leo-1',
      business_id: 'biz-1',
      channel: 'telegram' as const,
      bot_name: 'Léo',
      is_active: true,
      external_identifier_masked: '***6789',
    })
    const refresh = vi.fn()
    const patchChannel = vi.fn()
    const removeChannel = vi.fn()

    useLeoMock.mockReturnValue({
      channel,
      addonStatus: ref({ active: true, stripe_item_id: null }),
      loading: {
        fetch: ref(false),
        create: ref(false),
        update: ref(false),
        remove: ref(false),
        activateAddon: ref(false),
      },
      error: ref(null),
      refresh,
      createChannel: vi.fn(),
      patchChannel,
      removeChannel,
      activateAddon: vi.fn(),
    })

    const leo = useLeoChannels()

    expect(leo.channels.value).toEqual([channel.value])
    expect(leo.fetchChannels).toBe(refresh)
    expect(leo.updateChannel).toBe(patchChannel)
    expect(leo.deleteChannel).toBe(removeChannel)
  })

  it('returns an empty channels array when no leo channel is configured', () => {
    useLeoMock.mockReturnValue({
      channel: ref(null),
      addonStatus: ref({ active: false, stripe_item_id: null }),
      loading: {
        fetch: ref(false),
        create: ref(false),
        update: ref(false),
        remove: ref(false),
        activateAddon: ref(false),
      },
      error: ref(null),
      refresh: vi.fn(),
      createChannel: vi.fn(),
      patchChannel: vi.fn(),
      removeChannel: vi.fn(),
      activateAddon: vi.fn(),
    })

    const leo = useLeoChannels()

    expect(leo.channels.value).toEqual([])
  })
})
