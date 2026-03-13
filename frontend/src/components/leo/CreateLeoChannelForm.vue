<script setup lang="ts">
import { reactive, ref } from 'vue'

import LeoChannelTypeBadge from '@/components/leo/LeoChannelTypeBadge.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import type { LeoChannelType } from '@/types/leo'

const props = defineProps<{
  loading: boolean
}>()

const emit = defineEmits<{
  created: [{ channel: LeoChannelType; bot_name: string; external_identifier: string }]
  cancel: []
}>()

const form = reactive({
  bot_name: 'Léo',
  channel: 'telegram' as LeoChannelType,
  external_identifier: '',
})

const error = ref<string | null>(null)

const types: LeoChannelType[] = ['telegram', 'whatsapp', 'sms', 'slack', 'discord']

function submit() {
  error.value = null

  if (!form.bot_name.trim()) {
    error.value = 'Le nom du bot est obligatoire.'
    return
  }

  if (!form.external_identifier.trim()) {
    error.value = 'Le Chat ID Telegram est obligatoire.'
    return
  }

  emit('created', {
    channel: form.channel,
    bot_name: form.bot_name.trim(),
    external_identifier: form.external_identifier.trim(),
  })
}
</script>

<template>
  <div class="grid gap-5">
    <div class="flex items-center justify-between gap-4">
      <div>
        <p class="text-overline">Configuration</p>
        <h3 class="text-heading-3">Configurer Léo</h3>
      </div>
      <button
        type="button"
        class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700"
        @click="$emit('cancel')"
      >
        Fermer
      </button>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label for="leo-bot-name" class="text-label">Nom du bot</label>
        <input id="leo-bot-name" v-model="form.bot_name" type="text" class="mt-2 input-field" />
      </div>

      <div>
        <label for="leo-chat-id" class="text-label">Chat ID Telegram</label>
        <input
          id="leo-chat-id"
          v-model="form.external_identifier"
          type="text"
          class="mt-2 input-field"
          placeholder="123456789"
        />
      </div>
    </div>

    <div class="grid gap-3">
      <p class="text-label">Canal</p>
      <div class="grid gap-3 md:grid-cols-2">
        <label
          v-for="type in types"
          :key="type"
          class="flex items-center justify-between rounded-2xl border px-4 py-3"
          :class="
            type === 'telegram'
              ? 'border-emerald-300 bg-emerald-50'
              : 'cursor-not-allowed border-slate-200 bg-slate-50 opacity-70'
          "
        >
          <div class="flex items-center gap-3">
            <input
              v-model="form.channel"
              type="radio"
              name="leo-channel-type"
              :value="type"
              :disabled="type !== 'telegram'"
              class="h-4 w-4 text-emerald-600"
            />
            <span class="text-sm font-semibold capitalize">{{ type }}</span>
          </div>
          <LeoChannelTypeBadge :type="type" />
        </label>
      </div>
    </div>

    <details class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
      <summary class="cursor-pointer text-sm font-semibold text-slate-900">
        Comment obtenir votre Chat ID
      </summary>
      <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-slate-600">
        <li>Ouvrez Telegram et démarrez une conversation avec `@userinfobot`.</li>
        <li>Envoyez n’importe quel message.</li>
        <li>Copiez la valeur `Id` affichée par le bot.</li>
      </ol>
    </details>

    <p class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
      Un seul canal possible par établissement. Pour changer de canal, supprimez l’actuel et
      créez-en un nouveau.
    </p>

    <p v-if="error" class="rounded-2xl bg-red-100 px-4 py-3 text-sm text-red-800">
      {{ error }}
    </p>

    <div class="flex items-center justify-end gap-3">
      <button
        type="button"
        class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700"
        @click="$emit('cancel')"
      >
        Annuler
      </button>
      <button
        type="button"
        class="inline-flex items-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-70"
        :disabled="props.loading"
        @click="submit"
      >
        <LoadingSpinner v-if="props.loading" size="sm" label="Creation du canal en cours" />
        <span :class="{ 'ml-2': props.loading }">Créer le canal</span>
      </button>
    </div>
  </div>
</template>
