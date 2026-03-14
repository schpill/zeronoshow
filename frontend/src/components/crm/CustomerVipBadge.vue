<script setup lang="ts">
defineProps<{
  isVip: boolean
}>()
</script>

<template>
  <span
    v-if="isVip"
    class="inline-flex items-center rounded-full border border-amber-300 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800"
  >
    VIP
  </span>
</template>
