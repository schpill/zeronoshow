const IMPERSONATION_TOKEN_KEY = 'znz_impersonation_token'
const IMPERSONATION_EXPIRES_AT_KEY = 'znz_impersonation_expires_at'

function isExpired(expiresAt: string | null): boolean {
  if (!expiresAt) {
    return true
  }

  return new Date(expiresAt).getTime() <= Date.now()
}

export function clearImpersonationToken() {
  sessionStorage.removeItem(IMPERSONATION_TOKEN_KEY)
  sessionStorage.removeItem(IMPERSONATION_EXPIRES_AT_KEY)
}

export function getImpersonationToken(): string | null {
  const token = sessionStorage.getItem(IMPERSONATION_TOKEN_KEY)
  const expiresAt = sessionStorage.getItem(IMPERSONATION_EXPIRES_AT_KEY)

  if (!token || isExpired(expiresAt)) {
    clearImpersonationToken()
    return null
  }

  return token
}

export function getActiveBusinessToken(): string | null {
  return getImpersonationToken() ?? localStorage.getItem('znz_token')
}

export function storeImpersonationToken(
  token: string,
  expiresAt = new Date(Date.now() + 15 * 60 * 1000).toISOString(),
) {
  sessionStorage.setItem(IMPERSONATION_TOKEN_KEY, token)
  sessionStorage.setItem(IMPERSONATION_EXPIRES_AT_KEY, expiresAt)
}
