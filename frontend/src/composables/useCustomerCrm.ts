import { ref } from 'vue'

import { getCustomers, updateCustomerCrm, type UpdateCustomerCrmPayload } from '@/api/crm'
import type { ReservationCustomer } from '@/types/reservations'

function normalizeError(error: unknown): string {
  if (error instanceof Error) return error.message
  return 'Une erreur est survenue.'
}

export function useCustomerCrm() {
  const customers = ref<ReservationCustomer[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchCustomers(filters?: {
    is_vip?: boolean
    is_blacklisted?: boolean
    birthday_month?: number | null
  }) {
    loading.value = true
    error.value = null

    try {
      customers.value = await getCustomers(filters)
      return customers.value
    } catch (err) {
      error.value = normalizeError(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function saveCustomerCrm(customerId: string, payload: UpdateCustomerCrmPayload) {
    loading.value = true
    error.value = null

    try {
      const updated = await updateCustomerCrm(customerId, payload)
      customers.value = customers.value.map((customer) =>
        customer.id === customerId ? updated : customer,
      )
      return updated
    } catch (err) {
      error.value = normalizeError(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    customers,
    loading,
    error,
    fetchCustomers,
    updateCustomerCrm: saveCustomerCrm,
  }
}
