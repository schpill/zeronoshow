import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import StatusBadge from '@/components/StatusBadge.vue'
import type { ReservationStatus } from '@/types/reservations'

describe('StatusBadge', () => {
  it.each([
    ['pending_verification', 'À vérifier', 'bg-slate-100'],
    ['pending_reminder', 'Confirmé (rappel à venir)', 'bg-blue-100'],
    ['confirmed', 'Confirmé', 'bg-emerald-100'],
    ['cancelled_by_client', 'Annulé', 'bg-amber-100'],
    ['cancelled_no_confirmation', 'Annulé (pas de réponse)', 'bg-red-100'],
    ['no_show', 'No-show', 'bg-red-100'],
    ['show', 'Présent', 'bg-emerald-100'],
  ] as [ReservationStatus, string, string][])(
    'renders the right label and class for %s',
    (status, label, expectedClass) => {
      const wrapper = mount(StatusBadge, {
        props: {
          status,
        },
      })

      expect(wrapper.text()).toContain(label)
      expect(wrapper.attributes('aria-label')).toContain(label)
      expect(wrapper.classes()).toContain(expectedClass)
    },
  )
})
