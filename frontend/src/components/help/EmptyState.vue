<script setup lang="ts">
import { RouterLink } from 'vue-router'

withDefaults(
  defineProps<{
    icon: string
    title: string
    description: string
    actionLabel?: string
    actionTo?: string
  }>(),
  {
    description: '',
  },
)
</script>

<template>
  <div class="flex flex-col items-center justify-center rounded-[28px] border-2 border-dashed border-slate-200 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
    <span class="text-5xl">{{ icon }}</span>
    <h3 class="mt-4 text-lg font-bold text-slate-900 dark:text-slate-50">{{ title }}</h3>
    <p v-if="description" class="mt-2 max-w-sm text-sm text-slate-500 dark:text-slate-400">
      {{ description }}
    </p>
    <RouterLink
      v-if="actionLabel && actionTo"
      :to="actionTo"
      class="mt-6 inline-flex items-center rounded-2xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
    >
      {{ actionLabel }}
    </RouterLink>
  </div>
</template>
