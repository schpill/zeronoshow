<script setup lang="ts">
defineProps<{
  title: string
  message: string
}>()

defineEmits<{
  retry: []
}>()
</script>

<template>
  <section
    class="rounded-[28px] border border-red-200 bg-red-50 p-6 text-red-950 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-50"
  >
    <p class="text-overline text-red-700 dark:text-red-300">Erreur</p>
    <h2 class="mt-2 text-xl font-semibold">{{ title }}</h2>
    <p class="mt-3 text-sm text-red-800 dark:text-red-200">{{ message }}</p>
    <button
      type="button"
      class="mt-5 rounded-2xl bg-red-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-800 dark:bg-red-500 dark:text-slate-950 dark:hover:bg-red-400"
      @click="$emit('retry')"
    >
      Reessayer
    </button>
  </section>
</template>
