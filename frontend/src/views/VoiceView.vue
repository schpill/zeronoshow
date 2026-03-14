<script setup lang="ts">
import { onMounted, ref } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'
import ErrorMessage from '@/components/ErrorMessage.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import VoiceCapEditForm from '@/components/voice/VoiceCapEditForm.vue'
import VoiceCreditCard from '@/components/voice/VoiceCreditCard.vue'
import VoiceSettingsCard from '@/components/voice/VoiceSettingsCard.vue'
import VoiceTopUpModal from '@/components/voice/VoiceTopUpModal.vue'
import { useVoiceCredits } from '@/composables/useVoiceCredits'
import { useVoiceSettings } from '@/composables/useVoiceSettings'
import { useToast } from '@/composables/useToast'

const voiceCredits = useVoiceCredits()
const voiceSettings = useVoiceSettings()
const toast = useToast()
const showTopUpModal = ref(false)
const showCapEdit = ref(false)

async function loadVoice() {
  await Promise.all([voiceCredits.fetchStatus(), voiceSettings.fetchSettings()])
}

async function handleSaveCap(cents: number, autoRenew: boolean) {
  await voiceCredits.setCap(cents, autoRenew)
  showCapEdit.value = false
  toast.success('Budget appels mis à jour.')
}

onMounted(() => {
  void loadVoice()
})
</script>

<template>
  <AppLayout>
    <div
      v-if="voiceCredits.loading.value && !voiceCredits.status.value"
      class="flex min-h-[240px] items-center justify-center"
    >
      <LoadingSpinner size="lg" label="Chargement des crédits voix" />
    </div>

    <ErrorMessage
      v-else-if="voiceCredits.error.value"
      title="Impossible de charger la voix"
      :message="voiceCredits.error.value"
      @retry="loadVoice"
    />

    <template v-else-if="voiceCredits.status.value">
      <section class="mb-6 rounded-[32px] border border-slate-200 bg-white p-6">
        <p class="text-overline">Phase 8</p>
        <h1 class="mt-2 text-heading-2">Appels automatiques</h1>
        <p class="mt-3 max-w-2xl text-sm text-slate-600">
          Pilotez vos crédits voix, vos critères d’appel automatique et vos tentatives de relance.
        </p>
      </section>

      <div class="grid gap-6 lg:grid-cols-[1.1fr_1fr]">
        <div class="space-y-6">
          <VoiceCapEditForm
            v-if="showCapEdit"
            :initial-cap-cents="voiceCredits.status.value.monthly_cap_cents"
            :initial-auto-renew="voiceCredits.status.value.auto_renew"
            :loading="voiceCredits.loading.value"
            @save="handleSaveCap"
            @cancel="showCapEdit = false"
          />
          <VoiceCreditCard
            v-else
            :status="voiceCredits.status.value"
            @topup="showTopUpModal = true"
            @edit-cap="showCapEdit = true"
          />
        </div>
        <VoiceSettingsCard />
      </div>

      <VoiceTopUpModal
        v-model="showTopUpModal"
        :loading="voiceCredits.loading.value"
        @submit="voiceCredits.topUp"
      />
    </template>
  </AppLayout>
</template>
