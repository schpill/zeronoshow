<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

import AppLayout from '@/layouts/AppLayout.vue'
import CreateLeoChannelForm from '@/components/leo/CreateLeoChannelForm.vue'
import ErrorMessage from '@/components/ErrorMessage.vue'
import LeoChannelCard from '@/components/leo/LeoChannelCard.vue'
import WhatsAppCreditCard from '@/components/leo/WhatsAppCreditCard.vue'
import WhatsAppTopUpModal from '@/components/leo/WhatsAppTopUpModal.vue'
import WhatsAppCapEditForm from '@/components/leo/WhatsAppCapEditForm.vue'
import VoiceCreditCard from '@/components/voice/VoiceCreditCard.vue'
import VoiceTopUpModal from '@/components/voice/VoiceTopUpModal.vue'
import VoiceCapEditForm from '@/components/voice/VoiceCapEditForm.vue'
import LeoUpgradeBanner from '@/components/leo/LeoUpgradeBanner.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import WidgetSettingsCard from '@/components/widget/WidgetSettingsCard.vue'
import WidgetSettingsModal from '@/components/widget/WidgetSettingsModal.vue'
import WidgetEmbedCard from '@/components/widget/WidgetEmbedCard.vue'
import WidgetStatsCard from '@/components/widget/WidgetStatsCard.vue'
import { useLeo } from '@/composables/useLeo'
import { useWhatsAppCredits } from '@/composables/useWhatsAppCredits'
import { useVoiceCredits } from '@/composables/useVoiceCredits'
import { useWidgetSettings } from '@/composables/useWidgetSettings'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import type { LeoChannelPayload } from '@/api/leo'
import type { UpdateWidgetSettingsPayload } from '@/api/widgetSettings'

const auth = useAuthStore()
const toast = useToast()
const leo = useLeo()
const waCredits = useWhatsAppCredits()
const voiceCredits = useVoiceCredits()
const widgetSettings = useWidgetSettings()
const showCreateModal = ref(false)
const showTopUpModal = ref(false)
const showVoiceTopUpModal = ref(false)
const showCapEdit = ref(false)
const showVoiceCapEdit = ref(false)
const showWidgetSettingsModal = ref(false)

async function loadLeo() {
  await leo.refresh()
  if (leo.channel.value?.channel === 'whatsapp') {
    void waCredits.fetchStatus()
  }
  if (leo.channel.value?.channel === 'voice') {
    void voiceCredits.fetchStatus()
  }
  if (auth.user?.id) {
    void widgetSettings.fetch(auth.user.id)
    void widgetSettings.fetchStats(auth.user.id)
  }
}

async function handleCreate(payload: LeoChannelPayload) {
  try {
    await leo.createChannel(payload)
    showCreateModal.value = false
    toast.success('Canal Léo créé.')
    void loadLeo()
  } catch {
    // handled by composable
  }
}

async function handleToggle(isActive: boolean) {
  try {
    await leo.patchChannel({ is_active: isActive })
    toast.success(isActive ? 'Canal activé.' : 'Canal désactivé.')
  } catch {
    // handled by composable
  }
}

async function handleDelete() {
  try {
    await leo.removeChannel()
    toast.warning('Canal Léo supprimé.')
  } catch {
    // handled by composable
  }
}

async function handleActivateAddon() {
  try {
    const response = await leo.activateAddon()
    auth.user = auth.user ? { ...auth.user, leo_addon_active: response.activated } : auth.user
    await leo.refresh()
    toast.success('Léo a été activé.')
  } catch {
    // handled by composable
  }
}

async function handleTopUp(amountCents: number) {
  await waCredits.topUp(amountCents)
}

async function handleSaveCap(cents: number, autoRenew: boolean) {
  await waCredits.setCap(cents, autoRenew)
  showCapEdit.value = false
  toast.success('Budget mis à jour.')
}

async function handleSaveVoiceCap(cents: number, autoRenew: boolean) {
  await voiceCredits.saveCap(cents, autoRenew)
  showVoiceCapEdit.value = false
  toast.success('Budget appels mis à jour.')
}

async function handleSaveWidgetSettings(payload: UpdateWidgetSettingsPayload) {
  if (!auth.user?.id) return
  await widgetSettings.update(auth.user.id, payload)
  showWidgetSettingsModal.value = false
  toast.success('Paramètres du widget mis à jour.')
}

onMounted(() => {
  void loadLeo()
})
</script>

<template>
  <AppLayout>
    <div v-if="leo.loading.fetch.value" class="flex min-h-[240px] items-center justify-center">
      <LoadingSpinner size="lg" label="Chargement de Léo" />
    </div>

    <ErrorMessage
      v-else-if="leo.error.value"
      title="Impossible de charger Léo"
      :message="leo.error.value"
      @retry="loadLeo"
    />

    <template v-else>
      <section class="mb-6 rounded-[32px] border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <p class="text-overline">Assistant Telegram</p>
            <h1 class="mt-2 text-heading-2">Léo</h1>
            <p class="mt-3 max-w-2xl text-body-sm">
              Activez votre assistant, configurez un unique canal par établissement et recevez vos
              notifications métier en direct.
            </p>
          </div>
          <button
            v-if="leo.addonStatus.value.active && !leo.channel.value"
            type="button"
            class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white"
            @click="showCreateModal = true"
          >
            Configurer Léo
          </button>
        </div>
      </section>

      <LeoUpgradeBanner
        v-if="!leo.addonStatus.value.active"
        :loading="leo.loading.activateAddon.value"
        @activate="handleActivateAddon"
      />

      <section
        v-else-if="!leo.channel.value"
        class="rounded-[32px] border border-dashed border-slate-300 bg-white px-6 py-12 text-center"
      >
        <p class="text-overline">Aucun canal</p>
        <h2 class="mt-2 text-heading-3">Votre assistant attend sa configuration</h2>
        <p class="mx-auto mt-3 max-w-xl text-body-sm">
          Renseignez votre Chat ID Telegram pour recevoir les réponses de Léo et les alertes
          d’annulation.
        </p>
        <button
          type="button"
          class="mt-6 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white"
          @click="showCreateModal = true"
        >
          Configurer Léo
        </button>
      </section>

      <LeoChannelCard
        v-else
        :channel="leo.channel.value"
        :busy="leo.loading.update.value || leo.loading.remove.value"
        @toggle="handleToggle"
        @delete="handleDelete"
      />

      <template v-if="leo.channel.value?.channel === 'whatsapp' && waCredits.status.value">
        <WhatsAppCapEditForm
          v-if="showCapEdit"
          :initial-cap-cents="waCredits.status.value.monthly_cap_cents"
          :initial-auto-renew="waCredits.status.value.auto_renew"
          :loading="waCredits.loading.value"
          @save="handleSaveCap"
          @cancel="showCapEdit = false"
        />
        <WhatsAppCreditCard
          v-else
          :status="waCredits.status.value"
          @topup="showTopUpModal = true"
          @edit-cap="showCapEdit = true"
        />
      </template>

      <template v-if="leo.channel.value?.channel === 'voice' && voiceCredits.status.value">
        <VoiceCapEditForm
          v-if="showVoiceCapEdit"
          :initial-cap-cents="voiceCredits.status.value.monthly_cap_cents"
          :initial-auto-renew="voiceCredits.status.value.auto_renew"
          :loading="voiceCredits.loading.value"
          @save="handleSaveVoiceCap"
          @cancel="showVoiceCapEdit = false"
        />
        <VoiceCreditCard
          v-else
          :status="voiceCredits.status.value"
          @topup="showVoiceTopUpModal = true"
          @edit-cap="showVoiceCapEdit = true"
        />
      </template>

      <section class="mt-6 rounded-[32px] border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <p class="text-overline">Widget de réservation</p>
            <h2 class="mt-2 text-heading-3">Réservation en ligne</h2>
            <p class="mt-3 max-w-2xl text-body-sm">
              Permettez à vos clients de réserver directement depuis votre site ou un lien partagé.
            </p>
          </div>
        </div>
        <div v-if="widgetSettings.settings.value" class="mt-4 space-y-4">
          <WidgetSettingsCard
            :settings="widgetSettings.settings.value"
            :loading="widgetSettings.loading.value"
            @edit="showWidgetSettingsModal = true"
          />
          <WidgetStatsCard
            :stats="widgetSettings.stats.value"
            :loading="false"
          />
          <WidgetEmbedCard
            :embed-url="widgetSettings.settings.value.embed_url"
            :booking-url="widgetSettings.settings.value.booking_url"
            :accent-colour="widgetSettings.settings.value.accent_colour"
          />
        </div>
      </section>

      <section class="mt-6 rounded-[32px] border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <p class="text-overline">Phase 9</p>
            <h2 class="mt-2 text-heading-3">Réputation</h2>
            <p class="mt-3 max-w-2xl text-body-sm">
              Configurez les demandes d’avis post-visite et suivez les clics envoyés après un statut
              "présent".
            </p>
          </div>
          <RouterLink
            to="/reputation"
            class="rounded-2xl bg-amber-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-amber-600"
          >
            Ouvrir Réputation
          </RouterLink>
        </div>
      </section>

      <div
        v-if="showCreateModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4"
      >
        <div class="w-full max-w-3xl rounded-[32px] bg-white p-6 shadow-2xl">
          <CreateLeoChannelForm
            :loading="leo.loading.create.value"
            @cancel="showCreateModal = false"
            @created="handleCreate"
          />
        </div>
      </div>

      <WhatsAppTopUpModal
        v-model="showTopUpModal"
        :loading="waCredits.loading.value"
        @submit="handleTopUp"
      />
      <VoiceTopUpModal
        v-model="showVoiceTopUpModal"
        :loading="voiceCredits.loading.value"
        @submit="voiceCredits.topUp"
      />

      <div
        v-if="showWidgetSettingsModal && widgetSettings.settings.value"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4"
      >
        <div class="w-full max-w-lg rounded-[32px] bg-white p-6 shadow-2xl">
          <WidgetSettingsModal
            :settings="widgetSettings.settings.value"
            :loading="widgetSettings.loading.value"
            @save="handleSaveWidgetSettings"
            @cancel="showWidgetSettingsModal = false"
          />
        </div>
      </div>
    </template>
  </AppLayout>
</template>
