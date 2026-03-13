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
  opted_out?: boolean | null
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

export interface SmsLogRecord {
  id: string
  type: string
  status: 'queued' | 'sent' | 'delivered' | 'failed'
  phone: string
  body: string
  cost_eur: number | null
  queued_at?: string | null
  sent_at?: string | null
  delivered_at?: string | null
}

export interface DashboardStats {
  confirmed: number
  pending_verification: number
  pending_reminder: number
  cancelled: number
  no_show: number
  show: number
  total: number
}

export interface ReservationListResponse {
  reservations: ReservationRecord[]
  stats?: DashboardStats
}

export interface ReservationMutationResponse {
  reservation: ReservationRecord
  customer?: ReservationCustomer
  sms_logs?: SmsLogRecord[]
}

export interface DashboardResponse {
  reservations: ReservationRecord[]
  stats: DashboardStats
  sms_cost_this_month: number
  weekly_no_show_rate: number | null
}

export interface ReservationPayload {
  customer_name: string
  phone: string
  scheduled_at: string
  guests?: number
  notes?: string
  phone_verified?: boolean
}
