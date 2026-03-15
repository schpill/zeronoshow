<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'

import DarkModeToggle from '@/components/DarkModeToggle.vue'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const auth = useAuthStore()

const modules = [
  { slug: 'reservations', label: 'Réservations', icon: '📋' },
  { slug: 'sms', label: 'SMS', icon: '📱' },
  { slug: 'scoring', label: 'Score de fiabilité', icon: '📊' },
  { slug: 'widget', label: 'Widget', icon: '🌐' },
  { slug: 'waitlist', label: 'Liste d\'attente', icon: '⏳' },
  { slug: 'customers', label: 'Clients', icon: '👥' },
  { slug: 'reputation', label: 'Réputation', icon: '⭐' },
  { slug: 'leo', label: 'Léo', icon: '🤖' },
]

const currentModule = computed(() => route.params.module as string | undefined)
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-50">
    <!-- Top bar -->
    <header class="border-b border-slate-200 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-900/95">
      <div class="mx-auto flex min-h-14 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
          <div
            class="flex h-9 w-9 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-xs font-extrabold text-white shadow-lg shadow-emerald-500/20"
          >
            ZN
          </div>
          <div>
            <RouterLink to="/help" class="text-overline hover:text-emerald-600 dark:hover:text-emerald-400">
              ZeroNoShow
            </RouterLink>
            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Centre d'aide</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <DarkModeToggle />
          <RouterLink
            v-if="auth.isAuthenticated"
            to="/dashboard"
            class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
          >
            Mon tableau de bord
          </RouterLink>
          <RouterLink
            v-else
            to="/login"
            class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
          >
            Connexion
          </RouterLink>
        </div>
      </div>
    </header>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="flex gap-8">
        <!-- Sidebar -->
        <aside class="hidden w-56 shrink-0 lg:block">
          <nav class="sticky top-8 space-y-1">
            <RouterLink
              to="/help"
              class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition"
              :class="
                !currentModule
                  ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                  : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'
              "
            >
              <span>🏠</span> Accueil
            </RouterLink>

            <p class="px-3 pt-4 pb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
              Modules
            </p>

            <RouterLink
              v-for="mod in modules"
              :key="mod.slug"
              :to="`/help/${mod.slug}`"
              class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium transition"
              :class="
                currentModule === mod.slug
                  ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                  : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'
              "
            >
              <span>{{ mod.icon }}</span> {{ mod.label }}
            </RouterLink>
          </nav>
        </aside>

        <!-- Mobile nav -->
        <div class="mb-6 flex gap-2 overflow-x-auto lg:hidden">
          <RouterLink
            to="/help"
            class="shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold transition"
            :class="
              !currentModule
                ? 'bg-emerald-50 text-emerald-700'
                : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'
            "
          >
            Accueil
          </RouterLink>
          <RouterLink
            v-for="mod in modules"
            :key="mod.slug"
            :to="`/help/${mod.slug}`"
            class="shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold transition"
            :class="
              currentModule === mod.slug
                ? 'bg-emerald-50 text-emerald-700'
                : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'
            "
          >
            {{ mod.icon }} {{ mod.label }}
          </RouterLink>
        </div>

        <!-- Main content -->
        <main class="min-w-0 flex-1">
          <RouterView />
        </main>
      </div>
    </div>
  </div>
</template>
