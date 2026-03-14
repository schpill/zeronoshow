<script setup lang="ts">
import type { WaitlistEntry } from '@/api/waitlist'
import WaitlistStatusBadge from './WaitlistStatusBadge.vue'

defineProps<{
  entry: WaitlistEntry
}>()

const emit = defineEmits<{
  (e: 'remove', id: string): void
  (e: 'notify', id: string): void
}>()
</script>

<template>
  <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
      {{ entry.client_name }}
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
      {{ entry.client_phone }}
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
      {{ entry.party_size }}
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
      <WaitlistStatusBadge
        :status="entry.status"
        :label="entry.status_label"
        :expires-at="entry.expires_at"
      />
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
      {{ entry.slot_time }}
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
      <div class="flex justify-end gap-2">
        <button
          v-if="entry.status === 'pending'"
          @click="emit('notify', entry.id)"
          class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
          title="Notifier manuellement"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
          </svg>
        </button>
        <button
          v-if="entry.status === 'pending'"
          @click="emit('remove', entry.id)"
          class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
          title="Supprimer"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    </td>
  </tr>
</template>
