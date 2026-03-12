import { ref } from 'vue'

import { apiClient } from '@/api/axios'
import type { CustomerLookupResponse, ReservationPayload } from '@/types/reservations'

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
  }

  const errors = {
    create: ref<string | null>(null),
    fetch: ref<string | null>(null),
    lookup: ref<string | null>(null),
    show: ref<string | null>(null),
  }

  async function createReservation(payload: ReservationPayload) {
    loading.create.value = true
    errors.create.value = null

    try {
      return await apiClient.post<{ reservation: { id: string } }>('/reservations', payload)
    } catch (error) {
      errors.create.value = normalizeError(error)
      throw error
    } finally {
      loading.create.value = false
    }
  }

  async function fetchReservations(params: { date?: string; week?: string }) {
    loading.fetch.value = true
    errors.fetch.value = null

    try {
      const query = toQuery(params)
      return await apiClient.get<{ reservations: unknown[] }>(
        `/reservations${query ? `?${query}` : ''}`,
      )
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
      return await apiClient.get(`/reservations/${id}`)
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
      return await apiClient.get<CustomerLookupResponse>(
        `/customers/lookup?${toQuery({ phone })}`,
      )
    } catch (error) {
      errors.lookup.value = normalizeError(error)
      throw error
    } finally {
      loading.lookup.value = false
    }
  }

  return {
    loading,
    errors,
    createReservation,
    fetchReservations,
    fetchReservation,
    lookupCustomer,
  }
}
