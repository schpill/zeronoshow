<script setup lang="ts">
import { RouterLink, RouterView, useRouter } from 'vue-router'

import { useAdminStore } from '@/stores/admin'

const adminStore = useAdminStore()
const router = useRouter()

async function handleLogout() {
  await adminStore.logout()
  await router.push('/admin/login')
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <header class="bg-slate-900 text-white">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <div>
          <p class="text-overline text-slate-400">Administration</p>
          <h1 class="text-heading-4 text-white">ZeroNoShow Admin</h1>
        </div>
        <div class="flex items-center gap-4">
          <nav class="hidden gap-4 md:flex">
            <RouterLink
              to="/admin/dashboard"
              class="text-sm font-semibold text-slate-200 hover:text-white"
            >
              Dashboard
            </RouterLink>
            <RouterLink
              to="/admin/businesses"
              class="text-sm font-semibold text-slate-200 hover:text-white"
            >
              Businesses
            </RouterLink>
            <RouterLink
              to="/admin/audit"
              class="text-sm font-semibold text-slate-200 hover:text-white"
            >
              Audit
            </RouterLink>
            <a
              href="/api/docs"
              target="_blank"
              rel="noreferrer"
              class="text-sm font-semibold text-emerald-300 hover:text-emerald-200"
            >
              Documentation API
            </a>
          </nav>
          <span class="text-sm text-slate-300">{{ adminStore.admin?.name }}</span>
          <button
            class="rounded-2xl border border-slate-700 px-4 py-2 text-sm font-semibold text-white"
            @click="handleLogout"
          >
            Logout
          </button>
        </div>
      </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <RouterView />
    </main>
  </div>
</template>
