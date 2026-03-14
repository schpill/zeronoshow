<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-950 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 text-center">
      <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl shadow-xl border border-yellow-100 dark:border-yellow-900/30">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 dark:bg-yellow-900/30">
          <svg class="h-10 w-10 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-white">Lien expiré</h2>
        <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">
          Désolé, ce lien de confirmation n'est plus valide ou le créneau a été attribué à un autre client.
        </p>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">
          N'hésitez pas à vous réinscrire sur la liste d'attente pour un autre créneau.
        </p>
      </div>
    </div>
  </div>
</template>
