import { mount } from '@vue/test-utils'
import { nextTick, ref } from 'vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import LeoView from '@/views/LeoView.vue'

const refresh = vi.fn()
const createChannel = vi.fn()
const patchChannel = vi.fn()
const removeChannel = vi.fn()
const activateAddon = vi.fn()
const toastSuccess = vi.fn()
const toastWarning = vi.fn()

const channel = ref<null | Record<string, unknown>>(null)
const addonStatus = ref({ active: false, stripe_item_id: null as string | null })
const error = ref<string | null>(null)
const loading = {
  fetch: ref(false),
  create: ref(false),
  update: ref(false),
  remove: ref(false),
  activateAddon: ref(false),
}
const authState = ref({
  id: 'biz-1',
  name: 'Le Salon',
  email: 'owner@example.com',
  subscription_status: 'active',
  trial_ends_at: null,
  leo_addon_active: false,
})

vi.mock('@/composables/useLeo', () => ({
  useLeo: () => ({
    channel,
    addonStatus,
    loading,
    error,
    refresh,
    createChannel,
    patchChannel,
    removeChannel,
    activateAddon,
  }),
}))

vi.mock('@/composables/useToast', () => ({
  useToast: () => ({
    success: toastSuccess,
    warning: toastWarning,
    error: vi.fn(),
    dismiss: vi.fn(),
    toasts: ref([]),
  }),
}))

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => ({
    get user() {
      return authState.value
    },
    set user(value) {
      authState.value = value
    },
  }),
}))

function mountLeoView() {
  return mount(LeoView, {
    global: {
      stubs: {
        AppLayout: { template: '<div><slot /></div>' },
        LoadingSpinner: { template: '<div data-test="spinner">loading</div>' },
        ErrorMessage: {
          props: ['message'],
          emits: ['retry'],
          template: '<button data-test="error" @click="$emit(`retry`)">{{ message }}</button>',
        },
        LeoUpgradeBanner: {
          props: ['loading'],
          emits: ['activate'],
          template:
            '<button data-test="upgrade" @click="$emit(`activate`)">upgrade {{ loading }}</button>',
        },
        LeoChannelCard: {
          props: ['channel', 'busy'],
          emits: ['toggle', 'delete'],
          template:
            '<div data-test="channel-card"><button data-test="toggle" @click="$emit(`toggle`, false)">toggle</button><button data-test="delete" @click="$emit(`delete`)">delete</button>{{ channel.bot_name }}</div>',
        },
        CreateLeoChannelForm: {
          props: ['loading'],
          emits: ['cancel', 'created'],
          template:
            '<div data-test="create-form"><button data-test="create" @click="$emit(`created`, { channel: `telegram`, bot_name: `Léo`, external_identifier: `123456789` })">create</button><button data-test="cancel" @click="$emit(`cancel`)">cancel</button></div>',
        },
      },
    },
  })
}

describe('LeoView', () => {
  beforeEach(() => {
    refresh.mockReset().mockResolvedValue(undefined)
    createChannel.mockReset().mockResolvedValue({
      id: 'leo-1',
      business_id: 'biz-1',
      channel: 'telegram',
      bot_name: 'Léo',
      is_active: true,
      external_identifier_masked: '***6789',
    })
    patchChannel.mockReset().mockResolvedValue(undefined)
    removeChannel.mockReset().mockResolvedValue(undefined)
    activateAddon.mockReset().mockResolvedValue({ activated: true, checkout_url: null })
    toastSuccess.mockReset()
    toastWarning.mockReset()
    channel.value = null
    addonStatus.value = { active: false, stripe_item_id: null }
    error.value = null
    loading.fetch.value = false
    loading.create.value = false
    loading.update.value = false
    loading.remove.value = false
    loading.activateAddon.value = false
    authState.value = {
      id: 'biz-1',
      name: 'Le Salon',
      email: 'owner@example.com',
      subscription_status: 'active',
      trial_ends_at: null,
      leo_addon_active: false,
    }
  })

  it('loads leo on mount and shows the upgrade banner when the addon is inactive', async () => {
    const wrapper = mountLeoView()

    await Promise.resolve()
    await nextTick()

    expect(refresh).toHaveBeenCalled()
    expect(wrapper.find('[data-test="upgrade"]').exists()).toBe(true)
  })

  it('activates the addon and refreshes the page state', async () => {
    const wrapper = mountLeoView()

    await Promise.resolve()
    await nextTick()
    refresh.mockClear()

    await wrapper.get('[data-test="upgrade"]').trigger('click')
    await Promise.resolve()
    await nextTick()

    expect(activateAddon).toHaveBeenCalled()
    expect(refresh).toHaveBeenCalled()
    expect(toastSuccess).toHaveBeenCalledWith('Léo a été activé.')
    expect(authState.value.leo_addon_active).toBe(true)
  })

  it('opens the create modal when the addon is active and no channel exists', async () => {
    addonStatus.value = { active: true, stripe_item_id: null }

    const wrapper = mountLeoView()

    await Promise.resolve()
    await nextTick()
    await wrapper.get('button.rounded-2xl.bg-emerald-600').trigger('click')

    expect(wrapper.find('[data-test="create-form"]').exists()).toBe(true)
  })

  it('creates a channel from the modal and shows a success toast', async () => {
    addonStatus.value = { active: true, stripe_item_id: null }

    const wrapper = mountLeoView()

    await Promise.resolve()
    await nextTick()
    await wrapper.get('button.rounded-2xl.bg-emerald-600').trigger('click')
    await wrapper.get('[data-test="create"]').trigger('click')
    await Promise.resolve()
    await nextTick()

    expect(createChannel).toHaveBeenCalledWith({
      channel: 'telegram',
      bot_name: 'Léo',
      external_identifier: '123456789',
    })
    expect(toastSuccess).toHaveBeenCalledWith('Canal Léo créé.')
  })

  it('renders the existing channel and handles toggle/delete actions', async () => {
    addonStatus.value = { active: true, stripe_item_id: null }
    channel.value = {
      id: 'leo-1',
      business_id: 'biz-1',
      channel: 'telegram',
      bot_name: 'Léo',
      is_active: true,
      external_identifier_masked: '***6789',
    }

    const wrapper = mountLeoView()

    await Promise.resolve()
    await nextTick()

    expect(wrapper.text()).toContain('Léo')

    await wrapper.get('[data-test="toggle"]').trigger('click')
    await wrapper.get('[data-test="delete"]').trigger('click')
    await Promise.resolve()
    await nextTick()

    expect(patchChannel).toHaveBeenCalledWith({ is_active: false })
    expect(removeChannel).toHaveBeenCalled()
    expect(toastSuccess).toHaveBeenCalledWith('Canal désactivé.')
    expect(toastWarning).toHaveBeenCalledWith('Canal Léo supprimé.')
  })

  it('shows a retryable error state when leo loading fails', async () => {
    error.value = 'API down'

    const wrapper = mountLeoView()

    await Promise.resolve()
    await nextTick()
    refresh.mockClear()

    expect(wrapper.get('[data-test="error"]').text()).toContain('API down')

    await wrapper.get('[data-test="error"]').trigger('click')

    expect(refresh).toHaveBeenCalled()
  })
})
