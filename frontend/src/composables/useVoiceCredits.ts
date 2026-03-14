import { computed, ref } from 'vue'

import {
  getVoiceCreditStatus,
  initiateVoiceTopUp,
  setVoiceMonthlyCap,
  type VoiceCreditStatus,
} from '@/api/voiceCredits'
import { getVoiceSettings, updateVoiceSettings, type VoiceSettingsPayload } from '@/api/voiceCalls'

function normalizeError(error: unknown): string {
  if (error instanceof Error) return error.message
  return 'Une erreur est survenue.'
}

export function useVoiceCredits() {
  const status = ref<VoiceCreditStatus | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const balanceFormatted = computed(() => {
    if (!status.value) return '0,00 €'
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(
      status.value.balance_euros,
    )
  })

  const capFormatted = computed(() => {
    if (!status.value) return '0,00 €'
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(
      status.value.monthly_cap_euros,
    )
  })

  const balancePercent = computed(() => {
    if (!status.value || status.value.monthly_cap_cents <= 0) return 0
    const percent = (status.value.balance_cents / status.value.monthly_cap_cents) * 100
    return Math.min(100, Math.max(0, percent))
  })

  async function fetchStatus() {
    loading.value = true
    error.value = null

    try {
      status.value = await getVoiceCreditStatus()
    } catch (err) {
      error.value = normalizeError(err)
    } finally {
      loading.value = false
    }
  }

  async function topUp(amountCents: number) {
    loading.value = true
    error.value = null

    try {
      const { checkout_url } = await initiateVoiceTopUp(amountCents)
      window.location.href = checkout_url
    } catch (err) {
      error.value = normalizeError(err)
      loading.value = false
    }
  }

  async function saveCap(cents: number, autoRenew: boolean) {
    loading.value = true
    error.value = null

    try {
      status.value = await setVoiceMonthlyCap(cents, autoRenew)
    } catch (err) {
      error.value = normalizeError(err)
    } finally {
      loading.value = false
    }
  }

  async function fetchSettings() {
    loading.value = true
    error.value = null

    try {
      status.value = await getVoiceSettings()
    } catch (err) {
      error.value = normalizeError(err)
    } finally {
      loading.value = false
    }
  }

  async function saveSettings(payload: VoiceSettingsPayload) {
    loading.value = true
    error.value = null

    try {
      status.value = await updateVoiceSettings(payload)
    } catch (err) {
      error.value = normalizeError(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    status,
    loading,
    error,
    balanceFormatted,
    capFormatted,
    balancePercent,
    fetchStatus,
    fetchSettings,
    topUp,
    setCap: saveCap,
    saveCap,
    saveSettings,
  }
}
