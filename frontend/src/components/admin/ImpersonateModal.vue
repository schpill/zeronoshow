<script setup lang="ts">
const props = defineProps<{
  businessName: string
  open: boolean
}>()

const emit = defineEmits<{
  close: []
  confirm: []
}>()
</script>

<template>
  <div
    v-if="props.open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
  >
    <div class="w-full max-w-lg rounded-[28px] border border-slate-200 bg-white p-6 shadow-xl">
      <p class="text-overline">Impersonation</p>
      <h2 class="text-heading-4 mt-2">Accès temporaire</h2>
      <p class="text-body-sm mt-3">
        Vous allez accéder au tableau de bord de {{ props.businessName }} pendant 15 minutes.
      </p>
      <div class="mt-6 flex justify-end gap-3">
        <button
          class="rounded-2xl border border-slate-200 px-4 py-2 text-button"
          @click="emit('close')"
        >
          Annuler
        </button>
        <button
          data-testid="confirm-impersonation"
          class="rounded-2xl bg-emerald-500 px-4 py-2 text-button text-white"
          @click="emit('confirm')"
        >
          Confirmer
        </button>
      </div>
    </div>
  </div>
</template>
