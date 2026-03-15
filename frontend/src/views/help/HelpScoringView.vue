<script setup lang="ts">
import MermaidDiagram from '@/components/help/MermaidDiagram.vue'

const scoringDiagram = `flowchart LR
    A[Téléphone] --> B[Recherche historique inter-établissements]
    B --> C{Historique ?}
    C -- Non --> D[Score = null → traité comme À risque]
    C -- Oui --> E[score = présences / total_réservations]
    E --> F{Valeur du score}
    F -- ≥90% --> G[Fiable — pas de rappel]
    F -- 70-89% --> H[Moyen — 1 rappel]
    F -- <70% --> I[À risque — 2 rappels]`
</script>

<template>
  <article class="prose max-w-none">
    <h1 class="text-heading-2">Score de fiabilité</h1>
    <p class="text-body mt-4 dark:text-slate-300">
      Le score de fiabilité prédit la probabilité qu'un client se présente à sa réservation.
    </p>

    <section class="mt-8">
      <h2 class="text-heading-3">1. Calcul du score</h2>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        Le score est calculé à partir de l'historique du client
        <strong>à travers tous les établissements</strong>
        de la plateforme ZeroNoShow. Plus le nombre de réservations est élevé, plus le score est
        fiable.
      </p>
      <div
        class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20"
      >
        <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">Formule</p>
        <p class="mt-1 font-mono text-sm text-emerald-700 dark:text-emerald-400">
          score = présences / total_réservations × 100
        </p>
      </div>
    </section>

    <section class="mt-8">
      <h2 class="text-heading-3">2. Les 3 niveaux</h2>
      <MermaidDiagram :definition="scoringDiagram" />
      <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <div
          class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20"
        >
          <span
            class="inline-block rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-900 dark:bg-emerald-900/40 dark:text-emerald-300"
            >Fiable ≥90%</span
          >
          <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">
            Client régulier. Aucun rappel SMS n'est envoyé — le client vient de toute façon.
          </p>
        </div>
        <div
          class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20"
        >
          <span
            class="inline-block rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800 dark:bg-amber-900/20 dark:text-amber-300"
            >Moyen 70-89%</span
          >
          <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">
            Client correct. 1 rappel SMS est envoyé 2 heures avant le RDV.
          </p>
        </div>
        <div
          class="rounded-2xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20"
        >
          <span
            class="inline-block rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-800 dark:bg-red-900/20 dark:text-red-300"
            >À risque &lt;70%</span
          >
          <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">
            Client peu fiable. 2 rappels : à -2h et à -30min.
          </p>
        </div>
      </div>
    </section>

    <section class="mt-8">
      <h2 class="text-heading-3">3. Impact sur les rappels</h2>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        Le score détermine le comportement de rappel automatique :
      </p>
      <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-600 dark:text-slate-400">
        <li><strong>Fiable</strong> : aucun SMS — économisez du budget SMS</li>
        <li><strong>Moyen</strong> : 1 rappel à 2 heures avant</li>
        <li><strong>À risque</strong> : 2 rappels (2h et 30min avant)</li>
      </ul>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        Si le client n'a jamais été vu sur la plateforme (score null), il est traité comme "À
        risque" par précaution.
      </p>
    </section>
  </article>
</template>
