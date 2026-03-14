import { flushPromises, mount } from '@vue/test-utils'
import { ref } from 'vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import VoiceReturnView from '@/views/VoiceReturnView.vue'

const push = vi.fn()
const route = { query: { status: 'success' } }
const fetchStatus = vi.fn()

const status = ref({
  balance_cents: 800,
  balance_euros: 8,
  monthly_cap_cents: 1000,
  monthly_cap_euros: 10,
  auto_renew: true,
  auto_call_enabled: false,
  auto_call_score_threshold: null as number | null,
  auto_call_min_party_size: null as number | null,
  retry_count: 2,
  retry_delay_minutes: 10,
  is_channel_active: true,
  low_balance_warning: false,
})

vi.mock('vue-router', () => ({
  useRoute: () => route,
  useRouter: () => ({ push }),
}))

vi.mock('@/composables/useVoiceCredits', () => ({
  useVoiceCredits: () => ({
    status,
    balanceFormatted: ref('8,00 €'),
    fetchStatus,
  }),
}))

describe('VoiceReturnView', () => {
  beforeEach(() => {
    push.mockReset()
    fetchStatus.mockReset().mockResolvedValue(undefined)
    route.query.status = 'success'
  })

  it('shows success state after polling on status=success', async () => {
    const wrapper = mount(VoiceReturnView, {
      global: {
        stubs: {
          AppLayout: { template: '<div><slot /></div>' },
          LoadingSpinner: true,
        },
      },
    })

    await flushPromises()

    expect(fetchStatus).toHaveBeenCalled()
    expect(wrapper.text()).toContain('Rechargement réussi')
    expect(wrapper.text()).toContain('8,00 €')
  })

  it('shows cancelled state on status=cancel', async () => {
    route.query.status = 'cancel'

    const wrapper = mount(VoiceReturnView, {
      global: {
        stubs: {
          AppLayout: { template: '<div><slot /></div>' },
          LoadingSpinner: true,
        },
      },
    })

    await flushPromises()

    expect(wrapper.text()).toContain('Rechargement annulé')
  })
})
