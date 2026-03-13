import { mount } from '@vue/test-utils'
import { nextTick, ref } from 'vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import Dashboard from '@/pages/Dashboard.vue'

const fetchDashboard = vi.fn()
const updateStatus = vi.fn()
const loadingFetch = ref(false)
const loadingUpdateStatus = ref(false)

vi.mock('@/composables/useReservations', () => ({
  useReservations: () => ({
    fetchDashboard,
    loading: { fetch: loadingFetch, updateStatus: loadingUpdateStatus },
    updateStatus,
  }),
}))

vi.mock('@/composables/usePolling', () => ({
  usePolling: (callback: () => Promise<void>) => {
    void callback()
  },
}))

function createDashboardResponse() {
  return {
    reservations: [
      {
        id: 'res-1',
        customer_name: 'Marc',
        status: 'confirmed',
        scheduled_at: '2026-03-13T19:00:00Z',
        guests: 2,
        notes: null,
        phone_verified: true,
        reminder_2h_sent: false,
        reminder_30m_sent: false,
      },
      {
        id: 'res-2',
        customer_name: 'Lina',
        status: 'no_show',
        scheduled_at: '2026-03-14T19:00:00Z',
        guests: 4,
        notes: null,
        phone_verified: true,
        reminder_2h_sent: false,
        reminder_30m_sent: false,
      },
    ],
    stats: {
      confirmed: 1,
      pending_verification: 0,
      pending_reminder: 0,
      cancelled: 0,
      no_show: 1,
      show: 0,
      total: 2,
    },
    sms_cost_this_month: 1.2,
    weekly_no_show_rate: 50,
  }
}

function mountDashboard() {
  return mount(Dashboard, {
    global: {
      stubs: {
        AppLayout: { template: '<div><slot /></div>' },
        StatsBar: {
          props: ['stats'],
          template: '<section data-test="stats-bar">{{ stats.total }}</section>',
        },
        DateNavigator: {
          props: ['modelValue'],
          emits: ['update:modelValue'],
          template:
            '<button data-test="date-nav" @click="$emit(`update:modelValue`, `2026-03-14`)">{{ modelValue }}</button>',
        },
        ReservationForm: {
          template:
            '<button data-test="create-reservation" @click="$emit(`created`, { id: `res-3`, customer_name: `Nina`, status: `confirmed`, scheduled_at: `2026-03-13T21:00:00Z`, guests: 2, notes: null, phone_verified: true, reminder_2h_sent: false, reminder_30m_sent: false })">create</button>',
        },
        ReservationList: {
          props: ['reservations', 'loading'],
          template:
            '<div data-test="reservation-list">{{ loading ? `loading` : reservations.length === 0 ? `empty` : reservations.map((reservation) => reservation.customer_name).join(`,`) }}</div>',
        },
        ReservationRow: {
          props: ['reservation'],
          template:
            '<article data-test="reservation-row">{{ reservation.customer_name }}</article>',
        },
      },
    },
  })
}

describe('Dashboard', () => {
  beforeEach(() => {
    fetchDashboard.mockReset()
    updateStatus.mockReset()
    loadingFetch.value = false
    loadingUpdateStatus.value = false
  })

  it('renders stats and the daily reservation list from the dashboard payload', async () => {
    fetchDashboard.mockResolvedValue(createDashboardResponse())

    const wrapper = mountDashboard()

    await Promise.resolve()
    await nextTick()

    expect(fetchDashboard).toHaveBeenCalledWith({ date: expect.any(String), week: undefined })
    expect(wrapper.get('[data-test="stats-bar"]').text()).toContain('2')
    expect(wrapper.get('[data-test="reservation-list"]').text()).toContain('Marc,Lina')
    expect(wrapper.text()).toContain('1.20 € SMS')
    expect(wrapper.text()).toContain('50% no-show')
  })

  it('passes the loading state through to the reservation list', async () => {
    loadingFetch.value = true
    fetchDashboard.mockResolvedValue(createDashboardResponse())

    const wrapper = mountDashboard()

    await Promise.resolve()
    await nextTick()

    expect(wrapper.get('[data-test="reservation-list"]').text()).toBe('loading')
  })

  it('shows the empty state when the selected day has no reservation', async () => {
    fetchDashboard.mockResolvedValue({
      ...createDashboardResponse(),
      reservations: [],
      stats: {
        confirmed: 0,
        pending_verification: 0,
        pending_reminder: 0,
        cancelled: 0,
        no_show: 0,
        show: 0,
        total: 0,
      },
    })

    const wrapper = mountDashboard()

    await Promise.resolve()
    await nextTick()

    expect(wrapper.get('[data-test="reservation-list"]').text()).toBe('empty')
  })

  it('inserts a new reservation immediately after form creation', async () => {
    fetchDashboard.mockResolvedValue(createDashboardResponse())

    const wrapper = mountDashboard()

    await Promise.resolve()
    await nextTick()
    await wrapper.get('[data-test="create-reservation"]').trigger('click')
    await nextTick()

    expect(wrapper.text()).toContain('Réservation créée avec succès.')
    expect(wrapper.get('[data-test="stats-bar"]').text()).toContain('3')
    expect(wrapper.get('[data-test="reservation-list"]').text()).toContain('Nina')
  })

  it('changes the selected date and refetches when the navigator emits a new day', async () => {
    fetchDashboard.mockResolvedValue(createDashboardResponse())

    const wrapper = mountDashboard()

    await Promise.resolve()
    await nextTick()
    fetchDashboard.mockClear()

    await wrapper.get('[data-test="date-nav"]').trigger('click')
    await Promise.resolve()
    await nextTick()

    expect(fetchDashboard).toHaveBeenCalledWith({ date: '2026-03-14', week: undefined })
  })

  it('switches to weekly view and groups reservations by day', async () => {
    fetchDashboard.mockResolvedValue(createDashboardResponse())

    const wrapper = mountDashboard()

    await Promise.resolve()
    await nextTick()
    fetchDashboard.mockClear()

    await wrapper.get('button:nth-of-type(2)').trigger('click')
    await Promise.resolve()
    await nextTick()

    expect(fetchDashboard).toHaveBeenCalledWith({
      date: expect.any(String),
      week: expect.stringMatching(/^\d{4}-W\d{2}$/),
    })
    expect(wrapper.find('[data-test="reservation-list"]').exists()).toBe(false)
    expect(wrapper.findAll('[data-test="reservation-row"]')).toHaveLength(2)
    expect(wrapper.text()).toContain('vendredi 13 mars')
    expect(wrapper.text()).toContain('samedi 14 mars')
  })
})
