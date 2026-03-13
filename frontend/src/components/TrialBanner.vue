<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'

const props = defineProps<{
  subscriptionStatus: 'trial' | 'active' | 'cancelled'
  trialEndsAt: string | null
}>()

const storageKey = 'znz_trial_banner_dismissed'
const dismissed = ref(sessionStorage.getItem(storageKey) === '1')

const daysRemaining = computed(() => {
  if (!props.trialEndsAt) {
    return null
  }

  return Math.ceil((new Date(props.trialEndsAt).getTime() - Date.now()) / 86_400_000)
})

const visible = computed(
  () =>
    props.subscriptionStatus !== 'active' &&
    !dismissed.value &&
    daysRemaining.value !== null &&
    daysRemaining.value <= 7,
)

const toneClasses = computed(() =>
  (daysRemaining.value ?? 0) <= 3
    ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-200'
    : 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200',
)

const message = computed(() => {
  if ((daysRemaining.value ?? 0) < 0) {
    return 'Essai gratuit expiré. Abonnez-vous pour continuer.'
  }

  return `Votre essai expire dans ${daysRemaining.value} jour${daysRemaining.value === 1 ? '' : 's'}.`
})

function dismiss() {
  dismissed.value = true
  sessionStorage.setItem(storageKey, '1')
}
</script>

<template>
  <section
    v-if="visible"
    role="alert"
    class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border px-4 py-3"
    :class="toneClasses"
  >
    <p class="text-sm font-medium">
      {{ message }}
    </p>
    <div class="flex items-center gap-3">
      <RouterLink to="/subscription" class="text-sm font-semibold underline underline-offset-2">
        Gérer l’abonnement
      </RouterLink>
      <button type="button" class="text-sm font-semibold" @click="dismiss">Masquer</button>
    </div>
  </section>
</template>
