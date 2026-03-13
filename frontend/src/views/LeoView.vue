<script setup lang="ts">
import { onMounted, ref } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'
import CreateLeoChannelForm from '@/components/leo/CreateLeoChannelForm.vue'
import ErrorMessage from '@/components/ErrorMessage.vue'
import LeoChannelCard from '@/components/leo/LeoChannelCard.vue'
import LeoUpgradeBanner from '@/components/leo/LeoUpgradeBanner.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import { useLeo } from '@/composables/useLeo'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import type { LeoChannelType } from '@/types/leo'

const auth = useAuthStore()
const toast = useToast()
const leo = useLeo()
const showCreateModal = ref(false)

async function loadLeo() {
  await leo.refresh()
}

async function handleCreate(payload: {
  channel: LeoChannelType
  bot_name: string
  external_identifier: string
}) {
  try {
    await leo.createChannel(payload)
    showCreateModal.value = false
    toast.success('Canal Léo créé.')
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
    </template>
  </AppLayout>
</template>
