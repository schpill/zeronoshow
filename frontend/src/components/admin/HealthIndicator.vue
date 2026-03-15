<script setup lang="ts">
const props = defineProps<{
  status: 'ok' | 'warning' | 'error'
  label: string
  detail?: string
}>()

const dotClasses = {
  ok: 'bg-emerald-500',
  warning: 'bg-amber-400',
  error: 'bg-red-500',
}
</script>

<template>
  <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4">
    <span class="mt-1 h-3 w-3 rounded-full" :class="dotClasses[props.status]" />
    <div class="min-w-0">
      <p class="text-label">{{ label }}</p>
      <p v-if="detail" class="text-caption mt-1">{{ detail }}</p>
    </div>
  </div>
</template>
