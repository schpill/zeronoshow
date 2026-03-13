<script setup lang="ts">
import { computed } from 'vue'

import type { LeoChannelType } from '@/types/leo'

const props = defineProps<{
  type: LeoChannelType
}>()

const badge = computed(() => {
  const map: Record<LeoChannelType, { label: string; meta: string; classes: string }> = {
    telegram: {
      label: 'Telegram',
      meta: 'Actif',
      classes: 'border-blue-200 bg-blue-50 text-blue-700',
    },
    whatsapp: {
      label: 'WhatsApp',
      meta: 'Bientôt',
      classes: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    },
    sms: {
      label: 'SMS',
      meta: 'Bientôt',
      classes: 'border-slate-200 bg-slate-50 text-slate-700',
    },
    slack: {
      label: 'Slack',
      meta: 'Bientôt',
      classes: 'border-violet-200 bg-violet-50 text-violet-700',
    },
    discord: {
      label: 'Discord',
      meta: 'Bientôt',
      classes: 'border-indigo-200 bg-indigo-50 text-indigo-700',
    },
  }

  return map[props.type]
})
</script>

<template>
  <span
    class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold"
    :class="badge.classes"
  >
    <span>{{ badge.label }}</span>
    <span
      class="rounded-full bg-white/70 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
    >
      {{ badge.meta }}
    </span>
  </span>
</template>
