import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

import { apiClient } from '@/api/axios'
import type { AuthResponse, BusinessUser } from '@/types/auth'
import {
  clearImpersonationToken,
  getActiveBusinessToken,
  storeImpersonationToken,
} from '@/utils/impersonation'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<BusinessUser | null>(null)
  const token = ref<string | null>(getActiveBusinessToken())

  const isAuthenticated = computed(() => Boolean(token.value))
  const isOnActivePlan = computed(() => {
    if (!user.value) {
      return false
    }

    if (user.value.subscription_status === 'active') {
      return true
    }

    return (
      user.value.subscription_status === 'trial' &&
      new Date(user.value.trial_ends_at).getTime() > Date.now()
    )
  })

  function applyAuth(response: AuthResponse) {
    token.value = response.token
    user.value = response.business
    localStorage.setItem('znz_token', response.token)
  }

  async function login(email: string, password: string) {
    const response = await apiClient.post<AuthResponse>('/auth/login', { email, password })
    applyAuth(response)
  }

  async function register(payload: Record<string, unknown>) {
    const response = await apiClient.post<AuthResponse>('/auth/register', payload)
    applyAuth(response)
  }

  async function logout() {
    try {
      await apiClient.post('/auth/logout')
    } finally {
      clearImpersonationToken()
      token.value = null
      user.value = null
      localStorage.removeItem('znz_token')
    }
  }

  function startImpersonation(
    impersonationToken: string,
    expiresAt = new Date(Date.now() + 15 * 60 * 1000).toISOString(),
  ) {
    storeImpersonationToken(impersonationToken, expiresAt)
    token.value = impersonationToken
  }

  function captureImpersonationTokenFromUrl() {
    const url = new URL(window.location.href)
    const impersonationToken = url.searchParams.get('impersonation_token')

    if (!impersonationToken) {
      token.value = getActiveBusinessToken()
      return
    }

    startImpersonation(impersonationToken)
    url.searchParams.delete('impersonation_token')
    window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`)
  }

  return {
    user,
    token,
    isAuthenticated,
    isOnActivePlan,
    captureImpersonationTokenFromUrl,
    login,
    register,
    logout,
    startImpersonation,
  }
})
