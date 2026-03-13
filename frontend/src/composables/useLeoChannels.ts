import { computed } from 'vue'

import { useLeo } from '@/composables/useLeo'

export function useLeoChannels() {
  const leo = useLeo()

  const channels = computed(() => (leo.channel.value ? [leo.channel.value] : []))

  return {
    ...leo,
    channels,
    fetchChannels: leo.refresh,
    updateChannel: leo.patchChannel,
    deleteChannel: leo.removeChannel,
  }
}
