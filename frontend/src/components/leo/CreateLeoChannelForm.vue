<script setup lang="ts">
import { computed, reactive, ref } from 'vue'

import LeoChannelTypeBadge from '@/components/leo/LeoChannelTypeBadge.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import type { LeoChannelType } from '@/types/leo'

const props = defineProps<{
  loading: boolean
}>()

const emit = defineEmits<{
  created: [
    payload: {
      channel: LeoChannelType
      bot_name: string
      external_identifier: string
      monthly_cap_cents?: number
      auto_renew?: boolean
    },
  ]
  cancel: []
}>()

const form = reactive({
  bot_name: 'Léo',
  channel: 'telegram' as LeoChannelType,
  external_identifier: '',
  monthly_cap_euros: 5,
  auto_renew: true,
})

const error = ref<string | null>(null)

const types: LeoChannelType[] = ['telegram', 'whatsapp', 'voice', 'sms', 'slack', 'discord']
const isEnabled = (type: LeoChannelType) => ['telegram', 'whatsapp', 'voice'].includes(type)
const channelCopy: Record<
  LeoChannelType,
  { label: string; placeholder: string; setupTitle: string }
> = {
  telegram: {
    label: 'Identifiant du canal',
    placeholder: '123456789',
    setupTitle: 'Comment obtenir votre Chat ID Telegram',
  },
  whatsapp: {
    label: 'Identifiant du canal',
    placeholder: '+33612345678',
    setupTitle: 'Comment connecter votre numéro WhatsApp',
  },
  voice: {
    label: 'Numéro à appeler',
    placeholder: '+33612345678',
    setupTitle: 'Comment configurer les appels automatiques',
  },
  sms: {
    label: 'Identifiant du canal',
    placeholder: '+33612345678',
    setupTitle: 'Comment renseigner votre numéro SMS',
  },
  slack: {
    label: 'Identifiant du canal',
    placeholder: 'C0123456789',
    setupTitle: 'Comment connecter votre canal Slack',
  },
  discord: {
    label: 'Identifiant du canal',
    placeholder: '123456789012345678',
    setupTitle: 'Comment connecter votre salon Discord',
  },
}
const identifierLabel = computed(() => channelCopy[form.channel].label)
const identifierPlaceholder = computed(() => channelCopy[form.channel].placeholder)
const setupTitle = computed(() => channelCopy[form.channel].setupTitle)

function submit() {
  error.value = null

  if (!form.bot_name.trim()) {
    error.value = 'Le nom du bot est obligatoire.'
    return
  }

  if (!form.external_identifier.trim()) {
    error.value = 'Identifiant du canal est obligatoire.'
    return
  }

  if (['whatsapp', 'voice'].includes(form.channel) && form.monthly_cap_euros <= 0) {
    error.value =
      form.channel === 'voice'
        ? 'Un budget mensuel Appels est requis.'
        : 'Un budget mensuel WhatsApp est requis.'
    return
  }

  emit('created', {
    channel: form.channel,
    bot_name: form.bot_name.trim(),
    external_identifier: form.external_identifier.trim(),
    monthly_cap_cents: Math.round(form.monthly_cap_euros * 100),
    auto_renew: form.auto_renew,
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
        <label for="leo-chat-id" class="text-label">{{ identifierLabel }}</label>
        <input
          id="leo-chat-id"
          v-model="form.external_identifier"
          type="text"
          class="mt-2 input-field"
          :placeholder="identifierPlaceholder"
        />
      </div>
    </div>

    <div class="grid gap-3">
      <p class="text-label">Canal</p>
      <div class="grid gap-3 md:grid-cols-2">
        <label
          v-for="type in types"
          :key="type"
          class="flex items-center justify-between rounded-2xl border px-4 py-3 transition"
          :class="
            isEnabled(type)
              ? form.channel === type
                ? 'border-emerald-300 bg-emerald-50'
                : 'border-slate-200 bg-white hover:border-slate-300 cursor-pointer'
              : 'cursor-not-allowed border-slate-200 bg-slate-50 opacity-70'
          "
        >
          <div class="flex items-center gap-3">
            <input
              v-model="form.channel"
              type="radio"
              name="leo-channel-type"
              :value="type"
              :disabled="!isEnabled(type)"
              class="h-4 w-4 text-emerald-600"
            />
            <span class="text-sm font-semibold capitalize">{{ type }}</span>
          </div>
          <LeoChannelTypeBadge :type="type" />
        </label>
      </div>
    </div>

    <div
      v-if="['whatsapp', 'voice'].includes(form.channel)"
      class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-6"
    >
      <div class="flex items-center justify-between">
        <h4 class="text-sm font-bold text-emerald-900">
          {{ form.channel === 'voice' ? 'Budget mensuel Appels' : 'Budget mensuel WhatsApp' }}
        </h4>
        <div class="flex items-center gap-2">
          <input
            id="whatsapp-budget"
            v-model="form.monthly_cap_euros"
            type="number"
            min="1"
            max="100"
            class="w-20 rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm font-bold text-emerald-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
          />
          <span class="text-sm font-bold text-emerald-900">€ / mois</span>
        </div>
      </div>
      <p class="mt-2 text-xs text-emerald-700 leading-relaxed">
        {{
          form.channel === 'voice'
            ? 'Ce budget alimente les appels sortants Twilio et peut être renouvelé automatiquement.'
            : 'Ce montant sera prélevé immédiatement via Stripe, puis chaque 1er du mois. Les crédits non utilisés sont reportés sans limite.'
        }}
      </p>
      <label class="mt-4 flex cursor-pointer items-center gap-3">
        <input
          v-model="form.auto_renew"
          type="checkbox"
          class="h-4 w-4 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500"
        />
        <span class="text-xs font-semibold text-emerald-900"
          >Renouvellement automatique activé</span
        >
      </label>
    </div>

    <details class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
      <summary class="cursor-pointer text-sm font-semibold text-slate-900">
        {{ setupTitle }}
      </summary>
      <ol
        v-if="form.channel === 'telegram'"
        class="mt-3 list-decimal space-y-2 pl-5 text-sm text-slate-600"
      >
        <li>Ouvrez Telegram et démarrez une conversation avec `@userinfobot`.</li>
        <li>Envoyez n’importe quel message.</li>
        <li>Copiez la valeur `Id` affichée par le bot.</li>
      </ol>
      <ol
        v-else-if="form.channel === 'whatsapp'"
        class="mt-3 list-decimal space-y-2 pl-5 text-sm text-slate-600"
      >
        <li>
          Renseignez votre numéro de téléphone personnel (celui avec lequel vous souhaitez parler à
          Léo).
        </li>
        <li>Le format doit être international (ex: +33612345678).</li>
        <li>
          Une fois configuré, vous pourrez initier la conversation avec le numéro WhatsApp de
          ZeroNoShow.
        </li>
      </ol>
      <ol
        v-else-if="form.channel === 'voice'"
        class="mt-3 list-decimal space-y-2 pl-5 text-sm text-slate-600"
      >
        <li>Renseignez le numéro de téléphone sur lequel Léo doit appeler vos clients.</li>
        <li>Le format doit être international (ex: +33612345678).</li>
        <li>Le budget d'appels doit être configuré avant l'activation du canal.</li>
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
