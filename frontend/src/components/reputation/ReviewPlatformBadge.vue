<script setup lang="ts">
defineProps<{
  platform: 'google' | 'tripadvisor'
}>()
</script>

<template>
  <span
    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
    :class="
      platform === 'google'
        ? 'bg-blue-100 text-blue-800'
        : 'bg-emerald-100 text-emerald-800'
    "
  >
    {{ platform === 'google' ? 'Google' : 'TripAdvisor' }}
  </span>
</template>
