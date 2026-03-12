type HttpMethod = 'GET' | 'POST'

interface RequestOptions {
  method?: HttpMethod
  body?: unknown
}

export interface ApiError {
  status: number
  data: {
    message?: string
    errors?: Record<string, string[]>
  }
}

async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const token = localStorage.getItem('znz_token')
  const response = await fetch(`/api/v1${path}`, {
    method: options.method ?? 'GET',
    headers: {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    credentials: 'include',
    body: options.body ? JSON.stringify(options.body) : undefined,
  })

  if (response.status === 204) {
    return undefined as T
  }

  const data = (await response.json().catch(() => ({}))) as T

  if (!response.ok) {
    if (response.status === 401) {
      localStorage.removeItem('znz_token')
      window.location.assign('/login')
    }

    if (response.status === 402) {
      window.location.assign('/subscription')
    }

    throw {
      status: response.status,
      data: data as ApiError['data'],
    } satisfies ApiError
  }

  return data
}

export const apiClient = {
  get<T>(path: string) {
    return request<T>(path)
  },
  post<T>(path: string, body?: unknown) {
    return request<T>(path, { method: 'POST', body })
  },
}
