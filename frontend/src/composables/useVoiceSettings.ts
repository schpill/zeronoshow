import { ref } from 'vue'

import {
  getVoiceSettings,
  updateVoiceSettings,
  type VoiceSettingsPayload,
} from '@/api/voiceCalls'
import type { VoiceCreditStatus } from '@/api/voiceCredits'

function normalizeError(error: unknown): string {
  if (error instanceof Error) return error.message
  return 'Une erreur est survenue.'
}

export function useVoiceSettings() {
  const settings = ref<VoiceCreditStatus | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchSettings() {
    loading.value = true
    error.value = null

    try {
      settings.value = await getVoiceSettings()
    } catch (err) {
      error.value = normalizeError(err)
    } finally {
      loading.value = false
    }
  }

  async function updateSettings(payload: VoiceSettingsPayload) {
    loading.value = true
    error.value = null

    try {
      settings.value = await updateVoiceSettings(payload)
    } catch (err) {
      error.value = normalizeError(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    settings,
    loading,
    error,
    fetchSettings,
    updateSettings,
  }
}
