<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import LoadingSpinner from '@/components/LoadingSpinner.vue'
import { useAdminStore } from '@/stores/admin'

const router = useRouter()
const adminStore = useAdminStore()

const form = reactive({
  email: '',
  password: '',
})

const submitting = ref(false)
const message = ref('')

async function handleSubmit() {
  message.value = ''
  submitting.value = true

  try {
    await adminStore.login(form.email, form.password)
    await router.push('/admin/dashboard')
  } catch (error) {
    if (typeof error === 'object' && error !== null && 'status' in error) {
      const status = Reflect.get(error, 'status')

      if (status === 401) {
        message.value = 'Identifiants invalides'
      } else if (status === 429) {
        message.value = 'Trop de tentatives, réessayez dans 15 minutes'
      }
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <main class="flex min-h-[80vh] items-center justify-center">
    <div
      class="w-full max-w-md rounded-[32px] border border-slate-200 bg-white p-8 shadow-xl shadow-slate-200/40"
    >
      <p class="text-overline">Admin</p>
      <h1 class="text-heading-2 mt-2">Connexion opérateur</h1>
      <form class="mt-8 space-y-5" @submit.prevent="handleSubmit">
        <div>
          <label for="admin-email" class="text-label">Email</label>
          <input id="admin-email" v-model="form.email" type="email" class="input-field mt-2" />
        </div>
        <div>
          <label for="admin-password" class="text-label">Mot de passe</label>
          <input
            id="admin-password"
            v-model="form.password"
            type="password"
            class="input-field mt-2"
          />
        </div>
        <p v-if="message" class="rounded-2xl bg-red-100 px-4 py-3 text-sm text-red-800">
          {{ message }}
        </p>
        <button
          type="submit"
          class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-white"
          :disabled="submitting"
        >
          <LoadingSpinner v-if="submitting" size="sm" label="Connexion admin" />
          <span :class="{ 'ml-2': submitting }">Se connecter</span>
        </button>
      </form>
    </div>
  </main>
</template>
