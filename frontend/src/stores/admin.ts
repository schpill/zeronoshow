import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

import { adminApiClient } from '@/api/adminAxios'

export interface AdminUser {
  id: string
  name: string
  email: string
}

interface AdminAuthResponse {
  token: string
  admin: AdminUser
}

export const useAdminStore = defineStore('admin', () => {
  const admin = ref<AdminUser | null>(null)
  const token = ref<string | null>(localStorage.getItem('znz_admin_token'))

  const isAuthenticated = computed(() => Boolean(token.value))

  function applyAuth(response: AdminAuthResponse) {
    token.value = response.token
    admin.value = response.admin
    localStorage.setItem('znz_admin_token', response.token)
  }

  async function login(email: string, password: string) {
    const response = await adminApiClient.post<AdminAuthResponse>('/login', { email, password })
    applyAuth(response)
  }

  async function logout() {
    try {
      await adminApiClient.post('/logout')
    } finally {
      token.value = null
      admin.value = null
      localStorage.removeItem('znz_admin_token')
    }
  }

  return {
    admin,
    token,
    isAuthenticated,
    login,
    logout,
  }
})
