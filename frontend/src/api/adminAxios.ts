import axios from 'axios'

export interface AdminApiError {
  status: number
  headers?: Record<string, unknown>
  data: {
    message?: string
    errors?: Record<string, string[]>
  }
}

const instance = axios.create({
  baseURL: '/api/v1/admin',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
  },
})

instance.interceptors.request.use((config) => {
  const token = localStorage.getItem('znz_admin_token')

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

instance.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status as number | undefined
    const data = error.response?.data as AdminApiError['data'] | undefined
    const headers = (error.response?.headers ?? {}) as Record<string, unknown>

    if (status === 401) {
      localStorage.removeItem('znz_admin_token')
      window.location.assign('/admin/login')
    }

    throw {
      status: status ?? 500,
      headers,
      data: data ?? {},
    } satisfies AdminApiError
  },
)

export const adminApiClient = {
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
