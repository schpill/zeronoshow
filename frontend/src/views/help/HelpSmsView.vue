<script setup lang="ts">
import MermaidDiagram from '@/components/help/MermaidDiagram.vue'

const pipelineDiagram = `flowchart TD
    A[Réservation créée] --> B{Téléphone vérifié ?}
    B -- Oui --> C[Consultation du score]
    B -- Non --> D[Job SendVerificationSms]
    D --> E[SMS : lien de confirmation]
    E --> F{Action du client ?}
    F -- Confirme --> C
    F -- Pas de réponse, token expiré --> G[Statut : annulé]
    C --> H{Tier de score ?}
    H -- ≥90% Fiable --> I[Pas de rappel]
    H -- 70-89% Moyen --> J[Rappel à -2h]
    H -- <70% À risque --> K[Rappel à -2h]
    K --> L[Rappel à -30min]
    J --> M{Confirmation reçue ?}
    L --> M
    M -- Oui --> N[Statut : confirmé]
    M -- Non, -15min dépassé --> O[Statut : annulé]`
</script>

<template>
  <article class="prose max-w-none">
    <h1 class="text-heading-2">SMS</h1>
    <p class="text-body mt-4 dark:text-slate-300">
      Comprendre le pipeline SMS, les types de messages envoyés et les coûts associés.
    </p>

    <section class="mt-8">
      <h2 class="text-heading-3">1. Types de SMS</h2>
      <p class="text-body-sm mt-3 dark:text-slate-400">ZeroNoShow envoie deux types de SMS :</p>
      <div class="mt-4 grid gap-3 sm:grid-cols-2">
        <div
          class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900"
        >
          <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
            SMS de vérification
          </p>
          <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
            Envoyé lors de la création d'une réservation avec un numéro non vérifié. Contient un
            lien de confirmation.
          </p>
        </div>
        <div
          class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900"
        >
          <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">SMS de rappel</p>
          <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
            Envoyé selon le score de fiabilité du client. 1 ou 2 rappels selon le tier (Moyen ou À
            risque).
          </p>
        </div>
      </div>
    </section>

    <section class="mt-8">
      <h2 class="text-heading-3">2. Pipeline SMS</h2>
      <MermaidDiagram :definition="pipelineDiagram" />
    </section>

    <section class="mt-8">
      <h2 class="text-heading-3">3. Coût par SMS</h2>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        Le coût mensuel des SMS est affiché sur le Dashboard. Chaque SMS coûte environ 0,07 €. Les
        rappels sont inclus dans le coût total.
      </p>
      <p class="text-body-sm mt-2 dark:text-slate-400">
        Consultez le Dashboard pour voir le récapitulatif mensuel.
      </p>
    </section>

    <section class="mt-8">
      <h2 class="text-heading-3">4. Statuts de livraison</h2>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        Chaque SMS passe par les statuts suivants :
      </p>
      <ul class="mt-2 list-disc pl-5 text-sm text-slate-600 dark:text-slate-400">
        <li><strong>En file</strong> — le SMS est en attente d'envoi</li>
        <li><strong>Envoyé</strong> — transmis au réseau opérateur</li>
        <li><strong>Remis</strong> — livré sur le téléphone du client</li>
        <li><strong>Échoué</strong> — envoi impossible (numéro invalide, etc.)</li>
      </ul>
    </section>
  </article>
</template>
