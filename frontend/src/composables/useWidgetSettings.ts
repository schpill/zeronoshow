import { ref } from 'vue'
import {
  getWidgetSettings,
  updateWidgetSettings,
  getWidgetStats,
  type WidgetSettingsRecord,
  type WidgetStatsResponse,
  type UpdateWidgetSettingsPayload,
} from '@/api/widgetSettings'

function normalizeError(error: unknown): string {
  if (error instanceof Error) return error.message
  if (typeof error === 'object' && error !== null && 'data' in error) {
    const data = Reflect.get(error, 'data')
    if (typeof data === 'object' && data !== null && 'message' in data) {
      const message = Reflect.get(data, 'message')
      if (typeof message === 'string') return message
    }
  }
  return 'Une erreur est survenue.'
}

export function useWidgetSettings() {
  const settings = ref<WidgetSettingsRecord | null>(null)
  const stats = ref<WidgetStatsResponse | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetch(businessId: string) {
    loading.value = true
    error.value = null
    try {
      const response = await getWidgetSettings(businessId)
      settings.value = response.setting
    } catch (err) {
      error.value = normalizeError(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function update(businessId: string, payload: UpdateWidgetSettingsPayload) {
    loading.value = true
    error.value = null
    try {
      const response = await updateWidgetSettings(businessId, payload)
      settings.value = response.setting
    } catch (err) {
      error.value = normalizeError(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchStats(businessId: string) {
    try {
      const response = await getWidgetStats(businessId)
      stats.value = response
    } catch (err) {
      error.value = normalizeError(err)
    }
  }

  return {
    settings,
    stats,
    loading,
    error,
    fetch,
    update,
    fetchStats,
  }
}
