import { mount } from '@vue/test-utils'
import { nextTick, ref } from 'vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import VoiceSettingsCard from '@/components/voice/VoiceSettingsCard.vue'

const updateSettings = vi.fn()
const fetchSettings = vi.fn()

const settings = ref({
  balance_cents: 500,
  balance_euros: 5,
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

vi.mock('@/composables/useVoiceSettings', () => ({
  useVoiceSettings: () => ({
    settings,
    loading: ref(false),
    error: ref(null),
    fetchSettings,
    updateSettings,
  }),
}))

describe('VoiceSettingsCard', () => {
  beforeEach(() => {
    fetchSettings.mockReset()
    updateSettings.mockReset().mockResolvedValue(undefined)
    settings.value = {
      ...settings.value,
      auto_call_enabled: false,
      auto_call_score_threshold: null,
      auto_call_min_party_size: null,
    }
  })

  it('shows criteria fields when auto-call is enabled', async () => {
    const wrapper = mount(VoiceSettingsCard, {
      global: {
        stubs: { LoadingSpinner: true },
      },
    })

    expect(wrapper.find('#voice-score-threshold').exists()).toBe(false)

    await wrapper.get('[data-test="auto-call-toggle"]').setValue(true)
    await nextTick()

    expect(wrapper.find('#voice-score-threshold').exists()).toBe(true)
    expect(wrapper.find('#voice-min-party').exists()).toBe(true)
  })

  it('shows validation error when enabled without criteria', async () => {
    const wrapper = mount(VoiceSettingsCard, {
      global: {
        stubs: { LoadingSpinner: true },
      },
    })

    await wrapper.get('[data-test="auto-call-toggle"]').setValue(true)
    await wrapper.get('[data-test="save-settings"]').trigger('click')

    expect(wrapper.text()).toContain(
      'Définissez au moins un critère pour activer les appels automatiques.',
    )
    expect(updateSettings).not.toHaveBeenCalled()
  })

  it('saves settings when a criterion is provided', async () => {
    const wrapper = mount(VoiceSettingsCard, {
      global: {
        stubs: { LoadingSpinner: true },
      },
    })

    await wrapper.get('[data-test="auto-call-toggle"]').setValue(true)
    await wrapper.get('#voice-score-threshold').setValue('35')
    await wrapper.get('[data-test="save-settings"]').trigger('click')

    expect(updateSettings).toHaveBeenCalledWith({
      auto_call_enabled: true,
      score_threshold: 35,
      min_party_size: null,
      retry_count: 2,
      retry_delay_minutes: 10,
    })
  })
})
