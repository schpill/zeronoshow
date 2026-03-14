import { ref } from 'vue'

import {
  getReviewRequests,
  getReviewSettings,
  getReviewStats,
  updateReviewSettings,
  type ReviewRequest,
  type ReviewSettings,
  type ReviewSettingsPayload,
  type ReviewStats,
} from '@/api/crm'

function normalizeError(error: unknown): string {
  if (error instanceof Error) return error.message
  return 'Une erreur est survenue.'
}

export function useReviewSettings() {
  const settings = ref<ReviewSettings | null>(null)
  const requests = ref<ReviewRequest[]>([])
  const stats = ref<ReviewStats>({
    total_sent: 0,
    total_clicked: 0,
    click_rate_percent: 0,
  })
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchSettings() {
    loading.value = true
    error.value = null

    try {
      settings.value = await getReviewSettings()
      return settings.value
    } catch (err) {
      error.value = normalizeError(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchRequests(params?: {
    status?: string
    platform?: string
    date_from?: string
    date_to?: string
  }) {
    loading.value = true
    error.value = null

    try {
      const response = await getReviewRequests(params)
      requests.value = response.data
      return requests.value
    } catch (err) {
      error.value = normalizeError(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchStats() {
    loading.value = true
    error.value = null

    try {
      stats.value = await getReviewStats()
      return stats.value
    } catch (err) {
      error.value = normalizeError(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function saveSettings(payload: ReviewSettingsPayload) {
    loading.value = true
    error.value = null

    try {
      settings.value = await updateReviewSettings(payload)
      return settings.value
    } catch (err) {
      error.value = normalizeError(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    settings,
    requests,
    stats,
    loading,
    error,
    fetchSettings,
    fetchRequests,
    fetchStats,
    updateSettings: saveSettings,
  }
}
