<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    label: string
    value: string | number
    color?: 'green' | 'yellow' | 'red' | 'blue'
  }>(),
  {
    color: 'blue',
  },
)

const accentClasses = {
  blue: 'border-blue-300 bg-blue-100 text-blue-800',
  green: 'border-emerald-300 bg-emerald-100 text-emerald-900',
  yellow: 'border-amber-300 bg-amber-100 text-amber-800',
  red: 'border-red-300 bg-red-100 text-red-800',
}
</script>

<template>
  <article class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
    <span class="text-overline">{{ label }}</span>
    <div class="mt-4 flex items-start justify-between gap-3">
      <p class="text-heading-3">{{ value }}</p>
      <span class="rounded-full border px-2.5 py-1 text-badge" :class="accentClasses[props.color]">
        {{ props.color }}
      </span>
    </div>
  </article>
</template>
