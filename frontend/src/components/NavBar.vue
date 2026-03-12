<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const isOpen = ref(false)
const initials = computed(() =>
  (auth.user?.name ?? 'ZN')
    .split(' ')
    .slice(0, 2)
    .map((chunk) => chunk[0]?.toUpperCase() ?? '')
    .join(''),
)
</script>

<template>
  <header class="border-b border-slate-200 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-900/95">
    <div class="mx-auto flex min-h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
      <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-sm font-extrabold text-white shadow-lg shadow-emerald-500/20">
          {{ initials }}
        </div>
        <div>
          <p class="text-overline">ZeroNoShow</p>
          <p class="text-heading-4 !text-base dark:!text-slate-50">
            {{ auth.user?.name ?? 'Mon établissement' }}
          </p>
        </div>
      </div>

      <button
        class="rounded-lg border border-slate-200 p-2 text-slate-600 dark:border-slate-700 dark:text-slate-300 sm:hidden"
        type="button"
        aria-label="Ouvrir la navigation"
        @click="isOpen = !isOpen"
      >
        <span class="block h-0.5 w-5 bg-current" />
        <span class="mt-1 block h-0.5 w-5 bg-current" />
        <span class="mt-1 block h-0.5 w-5 bg-current" />
      </button>

      <nav
        class="hidden items-center gap-3 sm:flex"
        role="navigation"
        aria-label="Main navigation"
      >
        <a
          href="#reservation-form"
          class="inline-flex items-center rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-600"
        >
          Nouvelle réservation
        </a>
        <button
          type="button"
          class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:text-white"
          @click="auth.logout"
        >
          Logout
        </button>
      </nav>
    </div>

    <nav
      v-if="isOpen"
      class="border-t border-slate-200 px-4 py-4 sm:hidden dark:border-slate-800"
      role="navigation"
      aria-label="Main navigation"
    >
      <div class="flex flex-col gap-3">
        <a
          href="#reservation-form"
          class="inline-flex items-center justify-center rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-white"
        >
          Nouvelle réservation
        </a>
        <RouterLink
          to="/"
          class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200"
        >
          Dashboard
        </RouterLink>
        <button
          type="button"
          class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200"
          @click="auth.logout"
        >
          Logout
        </button>
      </div>
    </nav>
  </header>
</template>
