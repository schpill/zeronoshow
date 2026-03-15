import { createRouter, createWebHistory } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to, _from, savedPosition) {
    if (savedPosition) return savedPosition
    if (to.hash) {
      return new Promise((resolve) => {
        const poll = (attempts: number) => {
          const el = document.querySelector(to.hash)
          if (el) {
            resolve({ el: to.hash, behavior: 'smooth', top: 16 })
          } else if (attempts < 30) {
            setTimeout(() => poll(attempts + 1), 100)
          }
        }
        setTimeout(() => poll(0), 230)
      })
    }
    return { top: 0 }
  },
  routes: [
    // ── Pages publiques sans layout ────────────────────────────────────────────
    {
      path: '/',
      component: () => import('@/views/LandingView.vue'),
    },
    {
      path: '/help',
      component: () => import('@/layouts/HelpLayout.vue'),
      children: [
        {
          path: '',
          name: 'help-index',
          component: () => import('@/views/help/HelpIndexView.vue'),
        },
        {
          path: ':module',
          name: 'help-module',
          component: () => import('@/views/help/HelpModuleRouter.vue'),
        },
      ],
    },
    {
      path: '/login',
      component: () => import('@/pages/LoginPage.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/register',
      component: () => import('@/pages/RegisterPage.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/waitlist/confirmed',
      component: () => import('@/views/public/WaitlistConfirmedView.vue'),
    },
    {
      path: '/waitlist/expired',
      component: () => import('@/views/public/WaitlistExpiredView.vue'),
    },
    {
      path: '/waitlist/declined',
      component: () => import('@/views/public/WaitlistDeclinedView.vue'),
    },
    {
      path: '/join/:token',
      component: () => import('@/views/public/PublicWaitlistView.vue'),
    },
    {
      path: '/widget/:businessToken',
      name: 'booking-widget',
      component: () => import('@/views/public/BookingWidgetView.vue'),
    },
    {
      path: '/widget/:businessToken/success',
      name: 'booking-success',
      component: () => import('@/views/public/BookingSuccessView.vue'),
    },
    {
      path: '/widget/:businessToken/embed',
      name: 'booking-iframe',
      component: () => import('@/views/public/WidgetIframeEntrypoint.vue'),
    },

    // ── Shell authentifié (AppLayout permanent) ────────────────────────────────
    {
      path: '/',
      component: () => import('@/layouts/AppLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: 'dashboard',
          component: () => import('@/pages/Dashboard.vue'),
        },
        {
          path: 'leo',
          name: 'leo',
          component: () => import('@/views/LeoView.vue'),
        },
        {
          path: 'leo/whatsapp/topup/return',
          name: 'leo-whatsapp-return',
          component: () => import('@/views/WhatsAppReturnView.vue'),
        },
        {
          path: 'leo/voice/topup/return',
          name: 'leo-voice-return',
          component: () => import('@/views/VoiceReturnView.vue'),
        },
        {
          path: 'voice',
          name: 'voice',
          component: () => import('@/views/VoiceView.vue'),
        },
        {
          path: 'voice/topup/return',
          name: 'voice-return',
          component: () => import('@/views/VoiceReturnView.vue'),
        },
        {
          path: 'subscription',
          component: () => import('@/pages/SubscriptionPage.vue'),
        },
        {
          path: 'reservations/:id',
          component: () => import('@/pages/ReservationDetailPage.vue'),
        },
        {
          path: 'waitlist',
          component: () => import('@/views/WaitlistView.vue'),
        },
        {
          path: 'customers',
          component: () => import('@/views/CustomersView.vue'),
        },
        {
          path: 'reputation',
          component: () => import('@/views/ReputationView.vue'),
        },
      ],
    },
  ],
})

router.afterEach(() => {
  const main = document.getElementById('page-main')
  if (!main) return
  main.classList.remove('page-enter')
  void main.offsetWidth // force reflow
  main.classList.add('page-enter')
  main.addEventListener('animationend', () => main.classList.remove('page-enter'), { once: true })
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return '/login'
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return '/dashboard'
  }

  return true
})

export default router
