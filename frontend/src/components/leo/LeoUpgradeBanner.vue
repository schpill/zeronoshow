<script setup lang="ts">
import LoadingSpinner from '@/components/LoadingSpinner.vue'

defineProps<{
  loading: boolean
}>()

defineEmits<{
  activate: []
}>()
</script>

<template>
  <section class="rounded-[32px] border border-brand-200 bg-brand-50 p-6">
    <p class="text-overline text-brand-700">Léo add-on</p>
    <h2 class="mt-2 text-heading-3">Activez Léo</h2>
    <p class="mt-3 max-w-2xl text-body-sm">
      Votre assistant Telegram à 9€/mois pour suivre les réservations du jour, les annulations et
      les clients en attente directement depuis votre messagerie.
    </p>
    <button
      type="button"
      class="mt-5 inline-flex items-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-70"
      :disabled="loading"
      @click="$emit('activate')"
    >
      <LoadingSpinner v-if="loading" size="sm" label="Activation de Léo en cours" />
      <span :class="{ 'ml-2': loading }">Activer Léo</span>
    </button>
  </section>
</template>
