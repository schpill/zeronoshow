<script setup lang="ts">
import MermaidDiagram from '@/components/help/MermaidDiagram.vue'

const lifecycleDiagram = `stateDiagram-v2
    [*] --> pending_verification : Réservation créée (téléphone non vérifié)
    [*] --> pending_reminder : Réservation créée (téléphone vérifié)
    pending_verification --> pending_reminder : Client confirme via lien SMS
    pending_verification --> cancelled_no_confirmation : Token expiré
    pending_reminder --> confirmed : Client confirme le rappel
    pending_reminder --> cancelled_by_client : Client annule via SMS
    pending_reminder --> no_show : Établissement marque no-show
    confirmed --> show : Établissement marque présent
    confirmed --> no_show : Établissement marque no-show
    confirmed --> cancelled_by_client : Client annule
    show --> [*]
    no_show --> [*]
    cancelled_by_client --> [*]
    cancelled_no_confirmation --> [*]`
</script>

<template>
  <article class="prose max-w-none">
    <h1 class="text-heading-2">Réservations</h1>
    <p class="text-body mt-4 dark:text-slate-300">
      Gérez l'ensemble du cycle de vie de vos réservations, de la création au suivi post-visit.
    </p>

    <section class="mt-8">
      <h2 class="text-heading-3">1. Créer une réservation</h2>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        Depuis le Dashboard, cliquez sur "Nouvelle réservation" ou utilisez le formulaire intégré.
        Remplissez les champs obligatoires : nom du client, téléphone, date/heure et nombre de couverts.
      </p>
      <p class="text-body-sm mt-2 dark:text-slate-400">
        Si le numéro n'est pas encore vérifié, un SMS de confirmation sera envoyé automatiquement.
        Cochez "Numéro confirmé par appel" si le client a déjà été vérifié.
      </p>
      <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
        <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">Astuce</p>
        <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-400">
          Utilisez le champ téléphone pour faire une recherche client automatique.
          Le score de fiabilité s'affiche dès que le numéro est reconnu.
        </p>
      </div>
    </section>

    <section class="mt-8">
      <h2 class="text-heading-3">2. Statuts de réservation</h2>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        Chaque réservation passe par plusieurs statuts au cours de son cycle de vie.
      </p>
      <MermaidDiagram :definition="lifecycleDiagram" />
      <div class="mt-4 grid gap-3 sm:grid-cols-2">
        <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
          <span class="inline-block rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800">Rappel prévu</span>
          <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">SMS de rappel programmé selon le score de fiabilité.</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
          <span class="inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-900">Confirmé</span>
          <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Le client a confirmé sa présence.</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
          <span class="inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-900">Présent</span>
          <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Le client s'est présenté.</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
          <span class="inline-block rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-800">No-show</span>
          <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Le client ne s'est pas présenté.</p>
        </div>
      </div>
    </section>

    <section class="mt-8">
      <h2 class="text-heading-3">3. Marquer présent / absent</h2>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        Sur la ligne de réservation dans le Dashboard, cliquez sur le statut pour changer l'état.
        Les options "Présent" et "No-show" sont disponibles pour chaque réservation.
      </p>
    </section>

    <section class="mt-8">
      <h2 class="text-heading-3">4. Annulation automatique</h2>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        Les réservations dont le téléphone n'est pas vérifié sont automatiquement annulées si le
        client ne confirme pas dans le délai imparti. Le statut passe à "Pas de réponse".
      </p>
    </section>
  </article>
</template>
