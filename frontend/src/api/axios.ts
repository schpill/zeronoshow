import axios from 'axios'

export interface ApiError {
  status: number
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
    const status = error.response?.status as number | undefined
    const data = error.response?.data as ApiError['data'] | undefined

    if (status === 401) {
      localStorage.removeItem('znz_token')
      window.location.assign('/login')
    }

    if (status === 402) {
      window.location.assign('/subscription')
    }

    throw {
      status: status ?? 500,
      data: data ?? {},
    } satisfies ApiError
  },
)

export const apiClient = {
  async get<T>(path: string) {
    const response = await instance.get<T>(path)
    return response.data
  },
  async post<T>(path: string, body?: unknown) {
    const response = await instance.post<T>(path, body)
    return response.data
  },
}
