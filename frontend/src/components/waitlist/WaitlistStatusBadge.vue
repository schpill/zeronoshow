<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue'

const props = defineProps<{
  status: 'pending' | 'notified' | 'confirmed' | 'declined' | 'expired'
  label: string
  expiresAt?: string
}>()

const statusClasses = computed(() => {
  return {
    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': props.status === 'pending',
    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400':
      props.status === 'notified',
    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400':
      props.status === 'confirmed',
    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': props.status === 'declined',
    'bg-gray-200 text-gray-600 dark:bg-gray-600 dark:text-gray-400': props.status === 'expired',
  }
})

const countdown = ref('')

const updateCountdown = () => {
  if (!props.expiresAt || props.status !== 'notified') {
    countdown.value = ''
    return
  }

  const expires = new Date(props.expiresAt).getTime()
  const now = new Date().getTime()
  const diff = expires - now

  if (diff <= 0) {
    countdown.value = 'Expiré'
    return
  }

  const minutes = Math.floor(diff / 60000)
  const seconds = Math.floor((diff % 60000) / 1000)
  countdown.value = `${minutes}:${seconds.toString().padStart(2, '0')}`
}

let interval: ReturnType<typeof setInterval> | null = null

onMounted(() => {
  updateCountdown()
  interval = setInterval(updateCountdown, 1000)
})

onUnmounted(() => {
  if (interval) clearInterval(interval)
})
</script>

<template>
  <div class="flex flex-col items-center">
    <span
      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
      :class="statusClasses"
    >
      {{ label }}
    </span>
    <span
      v-if="countdown"
      class="text-[10px] mt-0.5 font-mono text-yellow-600 dark:text-yellow-400"
    >
      {{ countdown }}
    </span>
  </div>
</template>
