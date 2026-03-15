<script setup lang="ts"></script>

<template>
  <article class="prose max-w-none">
    <h1 class="text-heading-2">Widget de réservation</h1>
    <p class="text-body mt-4 dark:text-slate-300">
      Permettez à vos clients de réserver en ligne directement depuis votre site web ou un lien
      partagé.
    </p>

    <section class="mt-8">
      <h2 class="text-heading-3">1. Obtenir le lien widget</h2>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        Allez dans <strong>Léo → Widget de réservation</strong> pour trouver votre lien unique et le
        code d'intégration iframe. Le lien public permet la réservation sans connexion.
      </p>
    </section>

    <section class="mt-8">
      <h2 class="text-heading-3">2. Intégration iframe</h2>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        Copiez le code suivant dans votre site web :
      </p>
      <div class="mt-4 rounded-xl bg-slate-900 p-4 font-mono text-sm text-slate-100">
        &lt;iframe<br />
        &nbsp;&nbsp;src="https://zeronoshow.fr/widget/VOTRE_TOKEN/embed"<br />
        &nbsp;&nbsp;width="100%"<br />
        &nbsp;&nbsp;height="600"<br />
        &nbsp;&nbsp;frameborder="0"<br />
        &nbsp;&nbsp;allow="geolocation *"<br />
        &gt;&lt;/iframe&gt;
      </div>
    </section>

    <section class="mt-8">
      <h2 class="text-heading-3">3. Personnalisation</h2>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        Depuis les paramètres du widget, vous pouvez configurer :
      </p>
      <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-600 dark:text-slate-400">
        <li>Le nombre de couverts maximum</li>
        <li>L'intervalle de réservation (jours à l'avance)</li>
        <li>La couleur d'accent du widget</li>
        <li>Les horaires d'ouverture par jour</li>
      </ul>
    </section>

    <section class="mt-8">
      <h2 class="text-heading-3">4. Page de succès</h2>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        Après une réservation réussie, le client est redirigé vers une page de confirmation qui
        affiche le récapitulatif et le statut "Rappel prévu".
      </p>
    </section>
  </article>
</template>
