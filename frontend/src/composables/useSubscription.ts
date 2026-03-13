import { computed, ref } from 'vue'

import { apiClient } from '@/api/axios'

export interface SubscriptionSnapshot {
  subscription_status: 'trial' | 'active' | 'cancelled'
  trial_ends_at: string | null
  stripe_customer_id?: string | null
  leo_addon_active?: boolean
  leo_addon_stripe_item_id?: string | null
  sms_cost_this_month: number
}

export function useSubscription() {
  const subscription = ref<SubscriptionSnapshot | null>(null)
  const loading = ref(false)

  const daysUntilTrialEnd = computed(() => {
    if (!subscription.value?.trial_ends_at) {
      return null
    }

    return Math.ceil(
      (new Date(subscription.value.trial_ends_at).getTime() - Date.now()) / 86_400_000,
    )
  })

  async function fetchSubscription() {
    loading.value = true

    try {
      subscription.value = await apiClient.get<SubscriptionSnapshot>('/subscription')
      return subscription.value
    } finally {
      loading.value = false
    }
  }

  async function createCheckoutSession() {
    return apiClient.post<{ checkout_url: string }>('/subscription/checkout')
  }

  return {
    subscription,
    loading,
    daysUntilTrialEnd,
    fetchSubscription,
    createCheckoutSession,
  }
}
