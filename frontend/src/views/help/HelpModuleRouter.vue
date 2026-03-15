<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue'
import { useRoute } from 'vue-router'

import ErrorMessage from '@/components/ErrorMessage.vue'

const route = useRoute()

const MODULE_MAP: Record<string, ReturnType<typeof defineAsyncComponent>> = {
  reservations: defineAsyncComponent(() => import('@/views/help/HelpReservationsView.vue')),
  sms: defineAsyncComponent(() => import('@/views/help/HelpSmsView.vue')),
  scoring: defineAsyncComponent(() => import('@/views/help/HelpScoringView.vue')),
  widget: defineAsyncComponent(() => import('@/views/help/HelpWidgetView.vue')),
  waitlist: defineAsyncComponent(() => import('@/views/help/HelpWaitlistView.vue')),
  customers: defineAsyncComponent(() => import('@/views/help/HelpCustomersView.vue')),
  reputation: defineAsyncComponent(() => import('@/views/help/HelpReputationView.vue')),
  leo: defineAsyncComponent(() => import('@/views/help/HelpLeoView.vue')),
}

const moduleKey = computed(() => route.params.module as string)
const component = computed(() => MODULE_MAP[moduleKey.value] ?? null)
const isValid = computed(() => component.value !== null)
</script>

<template>
  <component :is="component" v-if="isValid" />
  <ErrorMessage
    v-else
    title="Module introuvable"
    :message="`Le module '${moduleKey}' n'existe pas dans le centre d'aide.`"
  />
</template>
