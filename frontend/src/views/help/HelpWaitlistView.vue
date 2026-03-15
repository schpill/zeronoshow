<script setup lang="ts">
</script>

<template>
  <article class="prose max-w-none">
    <h1 class="text-heading-2">Liste d'attente</h1>
    <p class="text-body mt-4 dark:text-slate-300">
      Gérez les clients en attente de libération d'un créneau et notifiez-les automatiquement.
    </p>

    <section class="mt-8">
      <h2 class="text-heading-3">1. Ajouter un client</h2>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        Cliquez sur "Ajouter manuellement" pour inscrire un client avec son nom, téléphone,
        le créneau souhaité et le nombre de couverts.
      </p>
    </section>

    <section class="mt-8">
      <h2 class="text-heading-3">2. Notifier les clients</h2>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        Quand un créneau se libère, cliquez sur "Notifier" pour envoyer un SMS au client en tête de file.
        Le client a un délai configurable pour confirmer ou décliner.
      </p>
    </section>

    <section class="mt-8">
      <h2 class="text-heading-3">3. Réordonner la file</h2>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        Glissez-déposez les entrées pour changer l'ordre de priorité dans la liste d'attente.
      </p>
    </section>

    <section class="mt-8">
      <h2 class="text-heading-3">4. Lien public</h2>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        Partagez le lien public pour permettre aux clients de s'inscrire eux-mêmes à la liste d'attente
        depuis leur téléphone. Le lien est configurable dans les paramètres.
      </p>
    </section>
  </article>
</template>
