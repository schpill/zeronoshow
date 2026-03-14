<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue'
import { getSlots } from '@/api/widget'

const props = defineProps<{
  businessToken: string
  accentColour: string
  maxAdvanceDays: number
}>()

const emit = defineEmits<{
  select: [date: string, time: string]
}>()

const today = new Date()
const currentMonth = ref(today.getMonth())
const currentYear = ref(today.getFullYear())
const selectedDate = ref<string | null>(null)
const availableSlots = ref<string[]>([])
const loadingSlots = ref(false)
const noSlots = ref(false)

const monthName = computed(() => {
  return new Date(currentYear.value, currentMonth.value).toLocaleDateString('fr-FR', {
    month: 'long',
    year: 'numeric',
  })
})

const calendarDays = computed(() => {
  const days: { date: string; day: number; inMonth: boolean; disabled: boolean }[] = []
  const firstDay = new Date(currentYear.value, currentMonth.value, 1)
  const lastDay = new Date(currentYear.value, currentMonth.value + 1, 0)
  const startDayOfWeek = (firstDay.getDay() + 6) % 7

  const prevMonthLast = new Date(currentYear.value, currentMonth.value, 0).getDate()
  for (let i = startDayOfWeek - 1; i >= 0; i--) {
    days.push({ date: '', day: prevMonthLast - i, inMonth: false, disabled: true })
  }

  for (let d = 1; d <= lastDay.getDate(); d++) {
    const date = new Date(currentYear.value, currentMonth.value, d)
    const diffDays = Math.ceil((date.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))
    const disabled = diffDays < 0 || diffDays > props.maxAdvanceDays
    const dateStr = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`
    days.push({ date: dateStr, day: d, inMonth: true, disabled })
  }

  const remaining = 42 - days.length
  for (let i = 1; i <= remaining; i++) {
    days.push({ date: '', day: i, inMonth: false, disabled: true })
  }

  return days
})

function prevMonth() {
  if (currentMonth.value === 0) {
    currentMonth.value = 11
    currentYear.value--
  } else {
    currentMonth.value--
  }
}

function nextMonth() {
  if (currentMonth.value === 11) {
    currentMonth.value = 0
    currentYear.value++
  } else {
    currentMonth.value++
  }
}

async function onDateClick(dateStr: string) {
  selectedDate.value = dateStr
  loadingSlots.value = true
  noSlots.value = false
  try {
    const response = await getSlots(props.businessToken, dateStr)
    availableSlots.value = response.slots
    if (response.slots.length === 0) {
      noSlots.value = true
    }
  } catch {
    availableSlots.value = []
    noSlots.value = true
  } finally {
    loadingSlots.value = false
  }
}

function onTimeClick(time: string) {
  if (selectedDate.value) {
    emit('select', selectedDate.value, time)
  }
}
</script>

<template>
  <div>
    <h3 class="text-heading-4 mb-4">Choisissez une date</h3>

    <div class="flex items-center justify-between mb-4">
      <button type="button" class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="prevMonth">
        &larr;
      </button>
      <span class="text-sm font-semibold text-slate-800 capitalize">{{ monthName }}</span>
      <button type="button" class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="nextMonth">
        &rarr;
      </button>
    </div>

    <div class="grid grid-cols-7 gap-1 mb-2">
      <span v-for="d in ['Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa', 'Di']" :key="d" class="text-center text-xs font-medium text-slate-400">
        {{ d }}
      </span>
    </div>

    <div class="grid grid-cols-7 gap-1">
      <button
        v-for="day in calendarDays"
        :key="day.day + String(day.inMonth)"
        type="button"
        :disabled="day.disabled || !day.inMonth"
        class="aspect-square rounded-lg text-sm font-medium transition-colors"
        :class="[
          !day.inMonth ? 'text-slate-300 cursor-default' : '',
          day.inMonth && day.disabled ? 'text-slate-300 cursor-not-allowed' : '',
          day.inMonth && !day.disabled && day.date !== selectedDate ? 'text-slate-700 hover:bg-slate-100 cursor-pointer' : '',
          day.date === selectedDate ? 'text-white cursor-pointer' : '',
        ]"
        :style="day.date === selectedDate ? { background: accentColour } : {}"
        @click="day.inMonth && !day.disabled && onDateClick(day.date)"
      >
        {{ day.inMonth ? day.day : '' }}
      </button>
    </div>

    <div v-if="selectedDate" class="mt-6">
      <h4 class="text-label mb-3">Créneaux disponibles le {{ selectedDate }}</h4>

      <div v-if="loadingSlots" class="text-sm text-slate-500">Chargement...</div>

      <div v-else-if="noSlots" class="text-sm text-red-600">Aucun créneau disponible</div>

      <div v-else class="grid grid-cols-3 sm:grid-cols-4 gap-2">
        <button
          v-for="slot in availableSlots"
          :key="slot"
          type="button"
          class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:border-slate-400"
          @click="onTimeClick(slot)"
        >
          {{ slot }}
        </button>
      </div>
    </div>
  </div>
</template>
