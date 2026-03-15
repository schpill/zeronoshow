<script setup lang="ts">
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import LoadingSpinner from '@/components/LoadingSpinner.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const form = reactive({
  name: '',
  email: '',
  business_name: '',
  phone: '',
  password: '',
  password_confirmation: '',
})

const fieldErrors = ref<Record<string, string[]>>({})
const generalError = ref<string | null>(null)
const submitting = ref(false)

async function handleSubmit() {
  fieldErrors.value = {}
  generalError.value = null
  submitting.value = true

  try {
    await auth.register({ ...form })
    await router.push('/dashboard')
  } catch (error) {
    if (typeof error === 'object' && error !== null && 'status' in error) {
      const data = Reflect.get(error, 'data')
      if (
        Reflect.get(error, 'status') === 422 &&
        typeof data === 'object' &&
        data !== null &&
        'errors' in data
      ) {
        fieldErrors.value = Reflect.get(data, 'errors') as Record<string, string[]>
        return
      }
    }

    generalError.value = 'Impossible de créer le compte.'
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
      class="w-full max-w-2xl rounded-[32px] border border-slate-200 bg-white p-8 shadow-xl shadow-slate-200/50 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20"
    >
      <RouterLink to="/" class="mb-6 inline-flex items-center gap-2 text-slate-500 hover:text-slate-900 dark:hover:text-slate-200 transition-colors text-sm">
        ← Retour à l'accueil
      </RouterLink>
      <p class="text-overline">Essai gratuit 14 jours</p>
      <h1 class="text-heading-2 mt-2 dark:text-slate-50">Créer votre espace</h1>

      <form class="mt-8 grid gap-5 md:grid-cols-2" @submit.prevent="handleSubmit">
        <div>
          <label for="name" class="text-label dark:text-slate-200">Nom</label>
          <input id="name" v-model="form.name" type="text" class="mt-2 input-field" />
          <p v-if="fieldErrors.name" class="mt-2 text-sm text-red-700 dark:text-red-300">
            {{ fieldErrors.name[0] }}
          </p>
        </div>
        <div>
          <label for="business_name" class="text-label dark:text-slate-200">Établissement</label>
          <input
            id="business_name"
            v-model="form.business_name"
            type="text"
            class="mt-2 input-field"
          />
          <p v-if="fieldErrors.business_name" class="mt-2 text-sm text-red-700 dark:text-red-300">
            {{ fieldErrors.business_name[0] }}
          </p>
        </div>
        <div>
          <label for="email" class="text-label dark:text-slate-200">Email</label>
          <input id="email" v-model="form.email" type="email" class="mt-2 input-field" />
          <p v-if="fieldErrors.email" class="mt-2 text-sm text-red-700 dark:text-red-300">
            {{ fieldErrors.email[0] }}
          </p>
        </div>
        <div>
          <label for="phone" class="text-label dark:text-slate-200">Téléphone</label>
          <input
            id="phone"
            v-model="form.phone"
            type="tel"
            placeholder="+33612345678"
            class="mt-2 input-field"
          />
          <p v-if="fieldErrors.phone" class="mt-2 text-sm text-red-700 dark:text-red-300">
            {{ fieldErrors.phone[0] }}
          </p>
        </div>
        <div>
          <label for="password" class="text-label dark:text-slate-200">Mot de passe</label>
          <input id="password" v-model="form.password" type="password" class="mt-2 input-field" />
          <p v-if="fieldErrors.password" class="mt-2 text-sm text-red-700 dark:text-red-300">
            {{ fieldErrors.password[0] }}
          </p>
        </div>
        <div>
          <label for="password_confirmation" class="text-label dark:text-slate-200"
            >Confirmation</label
          >
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            class="mt-2 input-field"
          />
        </div>

        <p
          v-if="generalError"
          class="md:col-span-2 rounded-2xl bg-red-100 px-4 py-3 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-300"
        >
          {{ generalError }}
        </p>

        <div class="md:col-span-2 flex flex-wrap items-center justify-between gap-3 pt-2">
          <RouterLink
            to="/login"
            class="text-body-sm font-semibold text-emerald-700 dark:text-emerald-400"
          >
            Déjà inscrit ? Se connecter
          </RouterLink>
          <button
            type="submit"
            :disabled="submitting"
            class="inline-flex items-center justify-center rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-600"
          >
            <LoadingSpinner v-if="submitting" size="sm" label="Creation du compte en cours" />
            <span :class="{ 'ml-2': submitting }">Créer le compte</span>
          </button>
        </div>
      </form>
    </div>
  </main>
</template>
