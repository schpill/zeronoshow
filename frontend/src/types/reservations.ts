export type ReliabilityTier = 'reliable' | 'average' | 'at_risk' | null

export interface CustomerLookupResponse {
  found: boolean
  reliability_score: number | null
  score_tier: ReliabilityTier
}

export interface ReservationPayload {
  customer_name: string
  phone: string
  scheduled_at: string
  guests?: number
  notes?: string
  phone_verified?: boolean
}
