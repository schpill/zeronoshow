import { createRouter, createWebHistory } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: () => import('@/views/LandingView.vue'),
    },
    {
      path: '/dashboard',
      component: () => import('@/pages/Dashboard.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/leo',
      name: 'leo',
      component: () => import('@/views/LeoView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/leo/whatsapp/topup/return',
      name: 'leo-whatsapp-return',
      component: () => import('@/views/WhatsAppReturnView.vue'),
      meta: { requiresAuth: true },
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
      path: '/subscription',
      component: () => import('@/pages/SubscriptionPage.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/reservations/:id',
      component: () => import('@/pages/ReservationDetailPage.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/waitlist',
      component: () => import('@/views/WaitlistView.vue'),
      meta: { requiresAuth: true },
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
  ],
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
