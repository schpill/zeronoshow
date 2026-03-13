import axios from 'axios'
import * as Sentry from '@sentry/vue'

import {
  buildRateLimitMessage,
  getRetryAfterSeconds,
  shouldRetryRateLimitedRequest,
} from '@/api/errorHandling'
import { useToast } from '@/composables/useToast'

export interface ApiError {
  status: number
  headers?: Record<string, unknown>
  data: {
    message?: string
    errors?: Record<string, string[]>
  }
}

const instance = axios.create({
  baseURL: '/api/v1',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
  },
})

instance.interceptors.request.use((config) => {
  const token = localStorage.getItem('znz_token')

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

instance.interceptors.response.use(
  (response) => response,
  async (error) => {
    const toast = useToast()
    const status = error.response?.status as number | undefined
    const data = error.response?.data as ApiError['data'] | undefined
    const headers = (error.response?.headers ?? {}) as Record<string, unknown>
    const requestConfig = (error.config ?? {}) as { method?: string; __rateLimitRetried?: boolean }

    if (status === 401) {
      localStorage.removeItem('znz_token')
      window.location.assign('/login')
    }

    if (status === 402) {
      window.location.assign('/subscription')
    }

    if (status === 429) {
      const retryAfterSeconds = getRetryAfterSeconds(headers)
      toast.warning(buildRateLimitMessage(retryAfterSeconds))

      if (
        shouldRetryRateLimitedRequest(
          requestConfig.method,
          status,
          Boolean(requestConfig.__rateLimitRetried),
        )
      ) {
        requestConfig.__rateLimitRetried = true

        await new Promise((resolve) => {
          window.setTimeout(resolve, (retryAfterSeconds ?? 1) * 1000)
        })

        return instance.request(requestConfig)
      }
    }

    if (status === 503 || !error.response) {
      toast.error('Service temporairement indisponible. Vos données sont sauvegardées.', {
        duration: 0,
      })
      Sentry.captureException(error)
    }

    throw {
      status: status ?? 500,
      headers,
      data: data ?? {},
    } satisfies ApiError
  },
)

export const apiClient = {
  async delete<T>(path: string) {
    const response = await instance.delete<T>(path)
    return response.data
  },
  async get<T>(path: string) {
    const response = await instance.get<T>(path)
    return response.data
  },
  async patch<T>(path: string, body?: unknown) {
    const response = await instance.patch<T>(path, body)
    return response.data
  },
  async post<T>(path: string, body?: unknown) {
    const response = await instance.post<T>(path, body)
    return response.data
  },
}
