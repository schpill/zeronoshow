export function getRetryAfterSeconds(headers: Record<string, unknown> | undefined): number | null {
  const raw = headers?.['retry-after']

  if (typeof raw !== 'string') {
    return null
  }

  const parsed = Number.parseInt(raw, 10)

  return Number.isFinite(parsed) ? parsed : null
}

export function shouldRetryRateLimitedRequest(
  method: string | undefined,
  status: number | undefined,
  alreadyRetried: boolean,
): boolean {
  return method?.toLowerCase() === 'get' && status === 429 && !alreadyRetried
}

export function buildRateLimitMessage(retryAfterSeconds: number | null): string {
  if (retryAfterSeconds === null) {
    return 'Trop de requêtes. Veuillez patienter un instant.'
  }

  return `Trop de requêtes. Veuillez patienter ${retryAfterSeconds} secondes.`
}
