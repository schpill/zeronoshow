import { ref } from 'vue'

import { apiClient } from '@/api/axios'
import type {
  CustomerLookupResponse,
  DashboardResponse,
  ReservationListResponse,
  ReservationMutationResponse,
  ReservationPayload,
  ReservationStatus,
} from '@/types/reservations'

function toQuery(params: Record<string, string | undefined>): string {
  const search = new URLSearchParams()

  Object.entries(params).forEach(([key, value]) => {
    if (value) {
      search.set(key, value)
    }
  })

  return search.toString()
}

function normalizeError(error: unknown): string {
  if (error instanceof Error) {
    return error.message
  }

  if (typeof error === 'object' && error !== null && 'data' in error) {
    const data = Reflect.get(error, 'data')
    if (typeof data === 'object' && data !== null && 'message' in data) {
      const message = Reflect.get(data, 'message')
      if (typeof message === 'string') {
        return message
      }
    }
  }

  return 'Une erreur est survenue.'
}

export function useReservations() {
  const loading = {
    create: ref(false),
    fetch: ref(false),
    lookup: ref(false),
    show: ref(false),
    updateStatus: ref(false),
  }

  const errors = {
    create: ref<string | null>(null),
    fetch: ref<string | null>(null),
    lookup: ref<string | null>(null),
    show: ref<string | null>(null),
    updateStatus: ref<string | null>(null),
  }

  async function createReservation(payload: ReservationPayload) {
    loading.create.value = true
    errors.create.value = null

    try {
      return await apiClient.post<ReservationMutationResponse>('/reservations', payload)
    } catch (error) {
      errors.create.value = normalizeError(error)
      throw error
    } finally {
      loading.create.value = false
    }
  }

  async function fetchReservations(params: { date?: string; week?: string; source?: string }) {
    loading.fetch.value = true
    errors.fetch.value = null

    try {
      const query = toQuery(params)
      return await apiClient.get<ReservationListResponse>(
        `/reservations${query ? `?${query}` : ''}`,
      )
    } catch (error) {
      errors.fetch.value = normalizeError(error)
      throw error
    } finally {
      loading.fetch.value = false
    }
  }

  async function fetchDashboard(params: { date?: string; week?: string }) {
    loading.fetch.value = true
    errors.fetch.value = null

    try {
      const query = toQuery(params)
      return await apiClient.get<DashboardResponse>(`/dashboard${query ? `?${query}` : ''}`)
    } catch (error) {
      errors.fetch.value = normalizeError(error)
      throw error
    } finally {
      loading.fetch.value = false
    }
  }

  async function fetchReservation(id: string) {
    loading.show.value = true
    errors.show.value = null

    try {
      return await apiClient.get<ReservationMutationResponse>(`/reservations/${id}`)
    } catch (error) {
      errors.show.value = normalizeError(error)
      throw error
    } finally {
      loading.show.value = false
    }
  }

  async function lookupCustomer(phone: string) {
    loading.lookup.value = true
    errors.lookup.value = null

    try {
      return await apiClient.get<CustomerLookupResponse>(`/customers/lookup?${toQuery({ phone })}`)
    } catch (error) {
      errors.lookup.value = normalizeError(error)
      throw error
    } finally {
      loading.lookup.value = false
    }
  }

  async function updateStatus(id: string, status: ReservationStatus) {
    loading.updateStatus.value = true
    errors.updateStatus.value = null

    try {
      return await apiClient.patch<ReservationMutationResponse>(`/reservations/${id}/status`, {
        status,
      })
    } catch (error) {
      errors.updateStatus.value = normalizeError(error)
      throw error
    } finally {
      loading.updateStatus.value = false
    }
  }

  async function initiateVoiceCall(id: string) {
    loading.updateStatus.value = true
    errors.updateStatus.value = null

    try {
      return await apiClient.post<{ queued: boolean }>(`/reservations/${id}/voice-call`)
    } catch (error) {
      errors.updateStatus.value = normalizeError(error)
      throw error
    } finally {
      loading.updateStatus.value = false
    }
  }

  return {
    loading,
    errors,
    createReservation,
    fetchDashboard,
    fetchReservations,
    fetchReservation,
    lookupCustomer,
    initiateVoiceCall,
    updateStatus,
  }
}
