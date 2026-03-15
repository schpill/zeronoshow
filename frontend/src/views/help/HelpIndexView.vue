<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const search = ref('')

interface HelpModule {
  slug: string
  icon: string
  title: string
  description: string
  keywords: string[]
}

const HELP_MODULES: HelpModule[] = [
  {
    slug: 'reservations',
    icon: '📋',
    title: 'Réservations',
    description:
      'Créer, gérer et suivre vos réservations. Comprend le cycle de vie complet et les statuts.',
    keywords: ['créer', 'annuler', 'statut', 'présence', 'no-show', 'confirmation', 'réservation'],
  },
  {
    slug: 'sms',
    icon: '📱',
    title: 'SMS',
    description:
      'Pipeline SMS, types de messages, coût par envoi et webhook de livraison.',
    keywords: ['sms', 'message', 'coût', 'livraison', 'webhook', 'verification', 'rappel'],
  },
  {
    slug: 'scoring',
    icon: '📊',
    title: 'Score de fiabilité',
    description:
      'Calcul du score, les 3 niveaux (Fiable, Moyen, À risque) et impact sur les rappels.',
    keywords: ['score', 'fiabilité', 'fiable', 'moyen', 'risque', 'rappel', 'tier'],
  },
  {
    slug: 'widget',
    icon: '🌐',
    title: 'Widget de réservation',
    description:
      'Obtenir le lien, intégrer en iframe, personnaliser et suivre les réservations en ligne.',
    keywords: ['widget', 'iframe', 'intégration', 'lien', 'personnalisation', 'en ligne'],
  },
  {
    slug: 'waitlist',
    icon: '⏳',
    title: 'Liste d\'attente',
    description:
      'Gérer votre liste d\'attente, notifier les clients et configurer le lien public.',
    keywords: ['attente', 'liste', 'notifier', 'créneau', 'libre', 'waitlist'],
  },
  {
    slug: 'customers',
    icon: '👥',
    title: 'Clients',
    description:
      'Fiche client, CRM, VIP, liste noire et historique des réservations.',
    keywords: ['client', 'crm', 'vip', 'liste noire', 'fiche', 'historique'],
  },
  {
    slug: 'reputation',
    icon: '⭐',
    title: 'Réputation',
    description:
      'Demandes d\'avis post-visite, intégration Google/TripAdvisor et suivi des clics.',
    keywords: ['avis', 'réputation', 'google', 'tripadvisor', 'clic', 'note'],
  },
  {
    slug: 'leo',
    icon: '🤖',
    title: 'Léo — Assistant IA',
    description:
      'Assistant Telegram/WhatsApp, notifications en direct et gestion des crédits.',
    keywords: ['leo', 'assistant', 'telegram', 'whatsapp', 'ia', 'chat', 'crédit'],
  },
]

const filteredModules = computed(() => {
  const q = search.value.toLowerCase().trim()
  if (!q) return HELP_MODULES

  return HELP_MODULES.filter(
    (m) =>
      m.title.toLowerCase().includes(q) ||
      m.description.toLowerCase().includes(q) ||
      m.keywords.some((k) => k.toLowerCase().includes(q)),
  )
})

const hasResults = computed(() => filteredModules.value.length > 0)
</script>

<template>
  <section class="mb-6 rounded-[32px] border border-slate-200 bg-white p-6">
    <p class="text-overline">Centre d'aide</p>
    <h1 class="text-heading-2 mt-2">Comment pouvons-nous vous aider ?</h1>
    <p class="text-body mt-3 max-w-2xl">
      Documentation complète de votre backoffice. Recherchez un sujet ou parcourez les modules
      ci-dessous.
    </p>

    <div class="mt-6">
      <label for="help-search" class="sr-only">Rechercher dans l'aide</label>
      <div class="relative">
        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
          🔍
        </span>
        <input
          id="help-search"
          v-model="search"
          type="search"
          placeholder="Rechercher un sujet..."
          class="w-full rounded-2xl border border-slate-200 py-3 pl-11 pr-4 text-body transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:border-emerald-400"
        />
      </div>
    </div>

    <div class="mt-4 flex items-center justify-between">
      <p class="text-caption dark:text-slate-400">
        {{ filteredModules.length }} module{{ filteredModules.length > 1 ? 's' : '' }}
      </p>
      <RouterLink
        v-if="auth.isAuthenticated"
        to="/dashboard"
        class="text-sm font-semibold text-emerald-700 transition hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300"
      >
        ← Retour au tableau de bord
      </RouterLink>
      <RouterLink
        v-else
        to="/"
        class="text-sm font-semibold text-emerald-700 transition hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300"
      >
        ← Retour à l'accueil
      </RouterLink>
    </div>
  </section>

  <template v-if="hasResults">
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <RouterLink
        v-for="mod in filteredModules"
        :key="mod.slug"
        :to="`/help/${mod.slug}`"
        class="group flex flex-col rounded-[28px] border border-slate-200 bg-white p-5 transition hover:border-emerald-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-900 dark:hover:border-emerald-700"
      >
        <span class="text-2xl">{{ mod.icon }}</span>
        <h2 class="text-heading-4 mt-3 group-hover:text-emerald-700 dark:group-hover:text-emerald-400">
          {{ mod.title }}
        </h2>
        <p class="text-body-sm mt-2 flex-1 dark:text-slate-400">
          {{ mod.description }}
        </p>
        <span class="mt-4 text-sm font-semibold text-emerald-600 dark:text-emerald-400">
          Lire la documentation →
        </span>
      </RouterLink>
    </div>
  </template>

  <template v-else>
    <div class="mt-12 flex flex-col items-center justify-center py-16 text-center">
      <span class="text-5xl">🔍</span>
      <h2 class="text-heading-4 mt-4">Aucun résultat</h2>
      <p class="text-body-sm mt-2 max-w-md dark:text-slate-400">
        Aucun module ne correspond à votre recherche. Essayez avec d'autres termes.
      </p>
    </div>
  </template>
</template>
