<script setup lang="ts"></script>

<template>
  <article class="prose max-w-none">
    <h1 class="text-heading-2">Réputation</h1>
    <p class="text-body mt-4 dark:text-slate-300">
      Automatisez les demandes d'avis post-visit et suivez votre réputation en ligne.
    </p>

    <section class="mt-8">
      <h2 class="text-heading-3">1. Configuration des avis</h2>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        Activez les demandes d'avis et choisissez la plateforme cible (Google ou TripAdvisor).
        Définissez le délai d'envoi après le statut "Présent" (par défaut : 2 heures).
      </p>
    </section>

    <section class="mt-8">
      <h2 class="text-heading-3">2. Plateformes intégrées</h2>
      <div class="mt-4 grid gap-3 sm:grid-cols-2">
        <div
          class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900"
        >
          <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Google Business</p>
          <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
            Lien direct vers la page d'avis Google. Nécessite un Google Place ID.
          </p>
        </div>
        <div
          class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900"
        >
          <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">TripAdvisor</p>
          <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
            Lien vers votre page TripAdvisor. Nécessite un Location ID.
          </p>
        </div>
      </div>
    </section>

    <section class="mt-8">
      <h2 class="text-heading-3">3. Suivi des demandes</h2>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        La table des demandes montre chaque envoi : client, date, statut (envoyé/ouvert/clické) et
        le lien cliqué. Consultez les statistiques pour suivre le taux de clic.
      </p>
    </section>
  </article>
</template>
