<script setup lang="ts">
import { ref } from 'vue'

import LeoChannelTypeBadge from '@/components/leo/LeoChannelTypeBadge.vue'
import type { LeoChannelRecord } from '@/types/leo'

const props = defineProps<{
  channel: LeoChannelRecord
  busy: boolean
}>()

const emit = defineEmits<{
  toggle: [value: boolean]
  delete: []
}>()

const confirmDelete = ref(false)
</script>

<template>
  <article class="grid gap-5 rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <p class="text-overline">Canal actif</p>
        <h2 class="mt-2 text-heading-3">{{ props.channel.bot_name }}</h2>
        <p class="mt-2 text-body-sm">
          Identifiant masqué:
          <span class="font-mono">{{ props.channel.external_identifier_masked }}</span>
        </p>
      </div>
      <LeoChannelTypeBadge :type="props.channel.channel" />
    </div>

    <div class="flex flex-wrap items-center gap-3">
      <button
        type="button"
        class="rounded-2xl px-4 py-2 text-sm font-semibold"
        :class="
          props.channel.is_active
            ? 'bg-emerald-600 text-white'
            : 'border border-slate-200 text-slate-700'
        "
        :disabled="props.busy"
        @click="emit('toggle', !props.channel.is_active)"
      >
        {{ props.channel.is_active ? 'Canal actif' : 'Réactiver le canal' }}
      </button>
      <button
        type="button"
        class="rounded-2xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700"
        :disabled="props.busy"
        @click="confirmDelete = true"
      >
        Changer de canal
      </button>
    </div>

    <div
      v-if="confirmDelete"
      class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900"
    >
      <p class="font-semibold">Cette action supprimera votre canal Léo actuel.</p>
      <p class="mt-2">Vous devrez en créer un nouveau. L’historique des messages sera conservé.</p>
      <div class="mt-4 flex items-center gap-3">
        <button
          type="button"
          class="rounded-xl bg-red-600 px-4 py-2 font-semibold text-white"
          :disabled="props.busy"
          @click="emit('delete')"
        >
          Confirmer la suppression
        </button>
        <button
          type="button"
          class="rounded-xl border border-slate-200 px-4 py-2 font-semibold text-slate-700"
          @click="confirmDelete = false"
        >
          Annuler
        </button>
      </div>
    </div>
  </article>
</template>
