export type SubscriptionStatus = 'trial' | 'active' | 'cancelled'

export interface BusinessUser {
  id: string
  name: string
  email: string
  subscription_status: SubscriptionStatus
  trial_ends_at: string
  leo_addon_active?: boolean
  onboarding_completed_at: string | null
}

export interface AuthResponse {
  token: string
  business: BusinessUser
}
