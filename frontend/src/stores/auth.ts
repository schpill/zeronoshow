import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

import { apiClient } from '@/api/axios'
import type { AuthResponse, BusinessUser } from '@/types/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<BusinessUser | null>(null)
  const token = ref<string | null>(localStorage.getItem('znz_token'))

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
      token.value = null
      user.value = null
      localStorage.removeItem('znz_token')
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    isOnActivePlan,
    login,
    register,
    logout,
  }
})
