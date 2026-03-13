<script setup lang="ts">
import { computed } from 'vue'

import { useToast } from '@/composables/useToast'

const toast = useToast()

const latestToastId = computed(() => toast.toasts.value.slice(-1)[0]?.id ?? null)

function handleEscape() {
  if (latestToastId.value) {
    toast.dismiss(latestToastId.value)
  }
}

const toneClasses = {
  success:
    'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900/60 dark:bg-emerald-950/80 dark:text-emerald-100',
  error:
    'border-red-200 bg-red-50 text-red-900 dark:border-red-900/60 dark:bg-red-950/80 dark:text-red-100',
  warning:
    'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/80 dark:text-amber-100',
} as const
</script>

<template>
  <div
    class="pointer-events-none fixed right-4 top-4 z-50 flex w-full max-w-sm flex-col gap-3"
    tabindex="-1"
    @keydown.esc="handleEscape"
  >
    <TransitionGroup name="toast">
      <article
        v-for="item in toast.toasts.value"
        :key="item.id"
        :class="toneClasses[item.type]"
        class="pointer-events-auto rounded-3xl border px-4 py-4 shadow-lg shadow-slate-900/10"
        role="alert"
        aria-live="polite"
      >
        <div class="flex items-start gap-3">
          <div class="mt-1 h-2.5 w-2.5 rounded-full bg-current opacity-70" />
          <div class="flex-1">
            <p class="text-sm font-semibold">{{ item.message }}</p>
            <div
              v-if="item.duration > 0"
              class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/40 dark:bg-slate-900/40"
            >
              <div class="h-full w-full rounded-full bg-current opacity-50" />
            </div>
          </div>
          <button
            type="button"
            class="rounded-full p-1 text-current/80 transition hover:bg-black/5 hover:text-current dark:hover:bg-white/10"
            aria-label="Fermer la notification"
            @click="toast.dismiss(item.id)"
          >
            ×
          </button>
        </div>
      </article>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition:
    opacity 0.18s ease,
    transform 0.18s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(16px);
}
</style>
