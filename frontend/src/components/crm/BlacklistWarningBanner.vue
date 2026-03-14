<script setup lang="ts">
import { ref, watch } from 'vue'

const props = defineProps<{
  visible: boolean
}>()

const dismissed = ref(false)

watch(
  () => props.visible,
  (visible) => {
    if (visible) dismissed.value = false
  },
)
</script>

<template>
  <div
    v-if="visible && !dismissed"
    class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200"
  >
    <div class="flex items-start justify-between gap-3">
      <p>Ce client est sur liste noire. Vérifiez avant de confirmer la réservation.</p>
      <button type="button" class="font-semibold" @click="dismissed = true">Fermer</button>
    </div>
  </div>
</template>
