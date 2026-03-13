<script setup lang="ts">
import { computed, ref } from 'vue'

import { applyTheme, getStoredTheme, type Theme } from '@/utils/theme'

const theme = ref<Theme>(getStoredTheme())

function toggleTheme() {
  theme.value = theme.value === 'dark' ? 'light' : 'dark'
  applyTheme(theme.value)
}

const icon = computed(() => (theme.value === 'dark' ? '☀' : '☾'))
</script>

<template>
  <button
    type="button"
    class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-100"
    aria-label="Basculer le thème"
    @click="toggleTheme"
  >
    {{ icon }}
  </button>
</template>
