import { ref, computed } from 'vue'
import {
  getWaitlistEntries,
  addWaitlistEntry,
  removeWaitlistEntry,
  reorderWaitlist,
  notifyEntry,
  getWaitlistSettings,
  updateWaitlistSettings,
  regeneratePublicLink,
  getPublicWaitlistInfo,
  joinWaitlistPublic,
  type WaitlistEntry,
  type WaitlistFilter,
  type CreateWaitlistEntryPayload,
  type WaitlistSettings,
} from '@/api/waitlist'
import { useToast } from './useToast'

export function useWaitlist() {
  const entries = ref<WaitlistEntry[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
  })

  const { success, error: errorToast } = useToast()

  const fetchEntries = async (filter?: WaitlistFilter, page = 1) => {
    loading.value = true
    error.value = null
    try {
      const response = await getWaitlistEntries({ ...filter, page })
      entries.value = response.data
      pagination.value = response.meta
    } catch (e: unknown) {
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      const errorMsg = (e as any).response?.data?.message || 'Erreur'
      errorToast(errorMsg)
    } finally {
      loading.value = false
    }
  }

  const addEntry = async (payload: CreateWaitlistEntryPayload) => {
    loading.value = true
    try {
      const newEntry = await addWaitlistEntry(payload)
      entries.value.push(newEntry.data)
      success('Client ajouté à la liste d\'attente')
      return newEntry.data
    } catch (e: unknown) {
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      const msg = (e as any).response?.data?.message || 'Erreur lors de l\'ajout'
      errorToast(msg)
      throw e
    } finally {
      loading.value = false
    }
  }


  const removeEntry = async (id: string) => {
    try {
      await removeWaitlistEntry(id)
      entries.value = entries.value.filter((e) => e.id !== id)
      success('Entrée supprimée')
    } catch {
      errorToast('Erreur lors de la suppression')
    }
  }

  const reorder = async (orderedIds: string[]) => {
    try {
      await reorderWaitlist(orderedIds)
      success('Ordre mis à jour')
    } catch {
      errorToast('Erreur lors de la réorganisation')
    }
  }

  const notify = async (id: string) => {
    try {
      await notifyEntry(id)
      success('Notification envoyée')
      // Update local status? Or refetch?
      const entry = entries.value.find((e) => e.id === id)
      if (entry) {
        entry.status = 'notified'
        entry.status_label = 'Notifié'
      }
    } catch {
      errorToast("Erreur lors de l'envoi de la notification")
    }
  }

  const pendingCount = computed(() => entries.value.filter((e) => e.status === 'pending').length)
  const notifiedCount = computed(() => entries.value.filter((e) => e.status === 'notified').length)

  const settings = ref<WaitlistSettings | null>(null)

  const fetchSettings = async () => {
    try {
      settings.value = await getWaitlistSettings()
    } catch {
      //
    }
  }

  const updateSettings = async (payload: Partial<WaitlistSettings>) => {
    try {
      const response = await updateWaitlistSettings(payload)
      if (settings.value) {
        settings.value = { ...settings.value, ...response.settings }
      }
      success('Paramètres mis à jour')
    } catch {
      errorToast('Erreur lors de la mise à jour des paramètres')
    }
  }

  const regenerateLink = async () => {
    try {
      const response = await regeneratePublicLink()
      if (settings.value) {
        settings.value = {
          ...settings.value,
          waitlist_public_token: response.waitlist_public_token,
          public_registration_url: response.public_registration_url,
        }
      }
      success('Lien régénéré')
    } catch {
      errorToast('Erreur lors de la régénération du lien')
    }
  }


  return {
    entries,
    loading,
    error,
    pagination,
    settings,
    fetchEntries,
    addEntry,
    removeEntry,
    reorder,
    notify,
    fetchSettings,
    updateSettings,
    regenerateLink,
    getPublicWaitlistInfo,
    joinWaitlistPublic,
    pendingCount,
    notifiedCount,
  }
}
