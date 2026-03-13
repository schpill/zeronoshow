import { describe, expect, it } from 'vitest'

import {
  buildRateLimitMessage,
  getRetryAfterSeconds,
  shouldRetryRateLimitedRequest,
} from '@/api/errorHandling'

describe('api error handling helpers', () => {
  it('parses retry-after values from headers', () => {
    expect(getRetryAfterSeconds({ 'retry-after': '12' })).toBe(12)
    expect(getRetryAfterSeconds({})).toBeNull()
  })

  it('only retries idempotent get requests once on 429', () => {
    expect(shouldRetryRateLimitedRequest('get', 429, false)).toBe(true)
    expect(shouldRetryRateLimitedRequest('post', 429, false)).toBe(false)
    expect(shouldRetryRateLimitedRequest('get', 503, false)).toBe(false)
    expect(shouldRetryRateLimitedRequest('get', 429, true)).toBe(false)
  })

  it('builds a user-facing rate limit message', () => {
    expect(buildRateLimitMessage(5)).toBe('Trop de requêtes. Veuillez patienter 5 secondes.')
    expect(buildRateLimitMessage(null)).toBe('Trop de requêtes. Veuillez patienter un instant.')
  })
})
