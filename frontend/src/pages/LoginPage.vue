<script setup lang="ts">
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import LoadingSpinner from '@/components/LoadingSpinner.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const form = reactive({
  email: '',
  password: '',
})

const fieldErrors = ref<Record<string, string[]>>({})
const generalError = ref<string | null>(null)
const submitting = ref(false)

async function handleSubmit() {
  fieldErrors.value = {}
  generalError.value = null
  submitting.value = true

  try {
    await auth.login(form.email, form.password)
    await router.push('/dashboard')
  } catch (error) {
    if (typeof error === 'object' && error !== null && 'status' in error) {
      const status = Reflect.get(error, 'status')
      if (status === 401) {
        generalError.value = 'Email ou mot de passe incorrect.'
        return
      }

      const data = Reflect.get(error, 'data')
      if (status === 422 && typeof data === 'object' && data !== null && 'errors' in data) {
        fieldErrors.value = Reflect.get(data, 'errors') as Record<string, string[]>
      }
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <main
    class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-12 dark:bg-slate-950"
  >
    <div
      class="w-full max-w-md rounded-[32px] border border-slate-200 bg-white p-8 shadow-xl shadow-slate-200/50 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20"
    >
      <RouterLink to="/" class="mb-6 inline-flex items-center gap-2 text-slate-500 hover:text-slate-900 dark:hover:text-slate-200 transition-colors text-sm">
        ← Retour à l'accueil
      </RouterLink>
      <p class="text-overline">Backoffice client</p>
      <h1 class="text-heading-2 mt-2 dark:text-slate-50">Connexion</h1>
      <p class="text-body-sm mt-3 dark:text-slate-400">
        Connectez-vous pour créer et suivre vos réservations.
      </p>

      <form class="mt-8 space-y-5" @submit.prevent="handleSubmit">
        <div>
          <label for="email" class="text-label dark:text-slate-200">Email</label>
          <input id="email" v-model="form.email" type="email" class="mt-2 input-field" />
          <p v-if="fieldErrors.email" class="mt-2 text-sm text-red-700 dark:text-red-300">
            {{ fieldErrors.email[0] }}
          </p>
        </div>

        <div>
          <label for="password" class="text-label dark:text-slate-200">Mot de passe</label>
          <input id="password" v-model="form.password" type="password" class="mt-2 input-field" />
          <p v-if="fieldErrors.password" class="mt-2 text-sm text-red-700 dark:text-red-300">
            {{ fieldErrors.password[0] }}
          </p>
        </div>

        <p
          v-if="generalError"
          class="rounded-2xl bg-red-100 px-4 py-3 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-300"
        >
          {{ generalError }}
        </p>

        <button
          type="submit"
          :disabled="submitting"
          class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-600"
        >
          <LoadingSpinner v-if="submitting" size="sm" label="Connexion en cours" />
          <span :class="{ 'ml-2': submitting }">Se connecter</span>
        </button>
      </form>

      <p class="text-body-sm mt-6 dark:text-slate-400">
        Pas encore de compte ?
        <RouterLink
          to="/register"
          class="font-semibold text-emerald-700 transition hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300"
        >
          Créer un compte
        </RouterLink>
      </p>
    </div>
  </main>
</template>
