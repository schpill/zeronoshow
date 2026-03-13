export type ReliabilityTier = 'reliable' | 'average' | 'at_risk' | null
export type ReservationStatus =
  | 'pending_verification'
  | 'pending_reminder'
  | 'confirmed'
  | 'cancelled_by_client'
  | 'cancelled_no_confirmation'
  | 'no_show'
  | 'show'

export interface CustomerLookupResponse {
  found: boolean
  reliability_score: number | null
  score_tier: ReliabilityTier
}

export interface ReservationCustomer {
  id: string
  phone: string
  reliability_score: number | null
  score_tier: ReliabilityTier
  reservations_count: number
  shows_count: number
  no_shows_count: number
  opted_out?: boolean
}

export interface ReservationRecord {
  id: string
  customer_name: string
  scheduled_at: string
  guests: number
  notes: string | null
  status: ReservationStatus
  phone_verified: boolean
  reminder_2h_sent: boolean
  reminder_30m_sent: boolean
  token_expires_at?: string | null
  created_at?: string | null
  status_changed_at?: string | null
  customer?: ReservationCustomer
  sms_count?: number
}

export interface ReservationListResponse {
  reservations: ReservationRecord[]
  stats?: {
    confirmed: number
    pending_verification: number
    pending_reminder: number
    cancelled: number
    no_show: number
    show: number
    total: number
  }
}

export interface ReservationMutationResponse {
  reservation: ReservationRecord
  customer?: ReservationCustomer
}

export interface ReservationPayload {
  customer_name: string
  phone: string
  scheduled_at: string
  guests?: number
  notes?: string
  phone_verified?: boolean
}
