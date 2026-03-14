<script setup lang="ts">
import { useRoute } from 'vue-router'

const route = useRoute()
const date = route.query.date as string | undefined
const time = route.query.time as string | undefined
const name = route.query.name as string | undefined
const party = route.query.party as string | undefined
</script>

<template>
  <div class="min-h-screen bg-slate-50 px-4 py-8">
    <div class="mx-auto max-w-lg text-center">
      <div class="rounded-[32px] border border-slate-200 bg-white p-8 shadow-sm">
        <div
          class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100"
        >
          <svg
            class="h-8 w-8 text-emerald-600"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>

        <h1 class="text-heading-3 mb-2">Réservation confirmée !</h1>

        <div class="mx-auto mt-6 max-w-xs space-y-2 text-left">
          <div v-if="name" class="flex justify-between text-sm">
            <span class="text-slate-500">Nom</span>
            <span class="font-medium text-slate-800">{{ name }}</span>
          </div>
          <div v-if="date" class="flex justify-between text-sm">
            <span class="text-slate-500">Date</span>
            <span class="font-medium text-slate-800">{{ date }}</span>
          </div>
          <div v-if="time" class="flex justify-between text-sm">
            <span class="text-slate-500">Heure</span>
            <span class="font-medium text-slate-800">{{ time }}</span>
          </div>
          <div v-if="party" class="flex justify-between text-sm">
            <span class="text-slate-500">Couverts</span>
            <span class="font-medium text-slate-800">{{ party }}</span>
          </div>
        </div>

        <p class="mt-6 text-sm text-slate-500">Vous recevrez un SMS de confirmation.</p>
      </div>
    </div>
  </div>
</template>
