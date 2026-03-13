<script setup lang="ts">
import { computed, ref } from 'vue'

import { applyTheme, getStoredTheme, type Theme } from '@/utils/theme'

const theme = ref<Theme>(getStoredTheme())

function toggleTheme() {
  theme.value = theme.value === 'dark' ? 'light' : 'dark'
  applyTheme(theme.value)
}

const isDark = computed(() => theme.value === 'dark')
</script>

<template>
  <button
    type="button"
    class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-100"
    aria-label="Basculer le thème"
    @click="toggleTheme"
  >
    <svg
      v-if="isDark"
      class="h-4 w-4"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      aria-hidden="true"
    >
      <circle cx="12" cy="12" r="5" />
      <path d="M12 1v2" />
      <path d="M12 21v2" />
      <path d="m4.22 4.22 1.42 1.42" />
      <path d="m18.36 18.36 1.42 1.42" />
      <path d="M1 12h2" />
      <path d="M21 12h2" />
      <path d="m4.22 19.78 1.42-1.42" />
      <path d="m18.36 5.64 1.42-1.42" />
    </svg>
    <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
      <path
        d="M21 12.79A9 9 0 0 1 11.21 3c0-.34.02-.68.06-1.01A1 1 0 0 0 9.9.95 10 10 0 1 0 23.05 14.1a1 1 0 0 0-1.04-1.31c-.33.03-.67.05-1.01.05Z"
      />
    </svg>
  </button>
</template>
