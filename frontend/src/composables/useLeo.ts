import { ref } from 'vue'

import {
  activateLeoAddon,
  createLeoChannel,
  deleteLeoChannel,
  getLeoAddonStatus,
  getLeoChannel,
  updateLeoChannel,
  type LeoChannelPayload,
  type LeoChannelUpdatePayload,
} from '@/api/leo'
import type { LeoAddonStatus, LeoChannelRecord } from '@/types/leo'

function normalizeError(error: unknown): string {
  if (error instanceof Error) {
    return error.message
  }

  if (typeof error === 'object' && error !== null && 'data' in error) {
    const data = Reflect.get(error, 'data')
    if (typeof data === 'object' && data !== null && 'message' in data) {
      const message = Reflect.get(data, 'message')
      if (typeof message === 'string') {
        return message
      }
    }
  }

  return 'Une erreur est survenue.'
}

export function useLeo() {
  const channel = ref<LeoChannelRecord | null>(null)
  const addonStatus = ref<LeoAddonStatus>({ active: false, stripe_item_id: null })
  const loading = {
    fetch: ref(false),
    create: ref(false),
    update: ref(false),
    remove: ref(false),
    activateAddon: ref(false),
  }
  const error = ref<string | null>(null)

  async function refresh() {
    loading.fetch.value = true
    error.value = null

    try {
      const [{ channel: currentChannel }, addon] = await Promise.all([
        getLeoChannel(),
        getLeoAddonStatus(),
      ])

      channel.value = currentChannel
      addonStatus.value = addon
    } catch (err) {
      error.value = normalizeError(err)
      throw err
    } finally {
      loading.fetch.value = false
    }
  }

  async function createChannel(payload: LeoChannelPayload) {
    loading.create.value = true
    error.value = null

    try {
      const response = await createLeoChannel(payload)
      channel.value = response.channel
      return response.channel
    } catch (err) {
      error.value = normalizeError(err)
      throw err
    } finally {
      loading.create.value = false
    }
  }

  async function patchChannel(payload: LeoChannelUpdatePayload) {
    if (!channel.value) return null

    loading.update.value = true
    error.value = null

    try {
      const response = await updateLeoChannel(channel.value.id, payload)
      channel.value = response.channel
      return response.channel
    } catch (err) {
      error.value = normalizeError(err)
      throw err
    } finally {
      loading.update.value = false
    }
  }

  async function removeChannel() {
    if (!channel.value) return

    loading.remove.value = true
    error.value = null

    try {
      await deleteLeoChannel(channel.value.id)
      channel.value = null
    } catch (err) {
      error.value = normalizeError(err)
      throw err
    } finally {
      loading.remove.value = false
    }
  }

  async function activateAddon() {
    loading.activateAddon.value = true
    error.value = null

    try {
      const response = await activateLeoAddon()
      addonStatus.value = { active: response.activated, stripe_item_id: addonStatus.value.stripe_item_id }
      return response
    } catch (err) {
      error.value = normalizeError(err)
      throw err
    } finally {
      loading.activateAddon.value = false
    }
  }

  return {
    channel,
    addonStatus,
    loading,
    error,
    refresh,
    createChannel,
    patchChannel,
    removeChannel,
    activateAddon,
  }
}
