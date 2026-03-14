<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  embedUrl: string | null
  bookingUrl: string | null
  accentColour: string
}>()

const iframeCode = computed(() => {
  if (!props.embedUrl) return ''
  return `<iframe src="${props.embedUrl}" width="400" height="700" frameborder="0" title="Réservation en ligne"></iframe>`
})

function copyIframe() {
  if (iframeCode.value) {
    navigator.clipboard.writeText(iframeCode.value)
  }
}

function copyLink() {
  if (props.bookingUrl) {
    navigator.clipboard.writeText(props.bookingUrl)
  }
}
</script>

<template>
  <div class="rounded-[32px] border border-slate-200 bg-white p-6">
    <p class="text-overline">Intégration</p>
    <h3 class="mt-2 text-heading-4">Lien & iframe</h3>

    <div v-if="bookingUrl" class="mt-4 space-y-4">
      <div>
        <p class="text-sm font-medium text-slate-700 mb-1">Lien direct</p>
        <div class="flex items-center gap-2">
          <code
            class="flex-1 truncate rounded-xl bg-slate-50 px-3 py-2 text-xs font-mono text-slate-600"
            >{{ bookingUrl }}</code
          >
          <button
            type="button"
            class="shrink-0 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
            @click="copyLink"
          >
            Copier le lien
          </button>
        </div>
      </div>

      <div>
        <p class="text-sm font-medium text-slate-700 mb-1">Code iframe</p>
        <div class="flex items-center gap-2">
          <code
            class="flex-1 truncate rounded-xl bg-slate-50 px-3 py-2 text-xs font-mono text-slate-600"
            >{{ iframeCode }}</code
          >
          <button
            type="button"
            class="shrink-0 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
            @click="copyIframe"
          >
            Copier
          </button>
        </div>
      </div>

      <div v-if="embedUrl">
        <a
          :href="embedUrl"
          target="_blank"
          class="inline-block text-sm font-medium transition-colors"
          :style="{ color: accentColour }"
        >
          Ouvrir le widget &rarr;
        </a>
      </div>
    </div>
  </div>
</template>
