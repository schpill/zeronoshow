<script setup lang="ts">
const props = defineProps<{
  modelValue: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

function formatDate(value: string) {
  return new Intl.DateTimeFormat('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(new Date(`${value}T12:00:00`))
}

function shiftDate(days: number) {
  const nextDate = new Date(`${props.modelValue}T12:00:00`)
  nextDate.setDate(nextDate.getDate() + days)
  emit('update:modelValue', nextDate.toISOString().slice(0, 10))
}

function goToToday() {
  emit('update:modelValue', new Date().toISOString().slice(0, 10))
}

const isToday = () => props.modelValue === new Date().toISOString().slice(0, 10)
</script>

<template>
  <div class="flex items-center gap-3">
    <button
      type="button"
      class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200"
      aria-label="Jour précédent"
      @click="shiftDate(-1)"
    >
      ←
    </button>
    <p class="text-label min-w-32 text-center capitalize dark:text-slate-100">
      {{ formatDate(modelValue) }}
    </p>
    <button
      type="button"
      class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200"
      aria-label="Jour suivant"
      @click="shiftDate(1)"
    >
      →
    </button>
    <button
      type="button"
      class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
      :disabled="isToday()"
      @click="goToToday"
    >
      Aujourd’hui
    </button>
  </div>
</template>
