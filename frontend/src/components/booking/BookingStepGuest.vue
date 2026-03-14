<script setup lang="ts">
import { ref, computed } from 'vue'

const props = defineProps<{
  maxPartySize: number
  accentColour: string
}>()

const emit = defineEmits<{
  submit: [details: { guest_name: string; guest_phone: string; party_size: number }]
}>()

const guestName = ref('')
const guestPhone = ref('')
const partySize = ref(1)

const isValid = computed(() => {
  return (
    guestName.value.trim().length > 0 &&
    /^\+[1-9]\d{7,14}$/.test(guestPhone.value) &&
    partySize.value >= 1 &&
    partySize.value <= props.maxPartySize
  )
})

function submit() {
  if (!isValid.value) return
  emit('submit', {
    guest_name: guestName.value.trim(),
    guest_phone: guestPhone.value.trim(),
    party_size: partySize.value,
  })
}
</script>

<template>
  <div>
    <h3 class="text-heading-4 mb-4">Vos coordonnées</h3>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="text-label mb-1 block" for="guest-name">Nom</label>
        <input
          id="guest-name"
          v-model="guestName"
          type="text"
          class="input-field"
          placeholder="Jean Dupont"
          required
        />
      </div>

      <div>
        <label class="text-label mb-1 block" for="guest-phone">Téléphone</label>
        <input
          id="guest-phone"
          v-model="guestPhone"
          type="tel"
          class="input-field"
          placeholder="+33 6 12 34 56 78"
          required
        />
        <p class="text-caption mt-1">Format international (+33 6...)</p>
      </div>

      <div>
        <label class="text-label mb-1 block" for="party-size">Nombre de couverts</label>
        <input
          id="party-size"
          v-model.number="partySize"
          type="number"
          class="input-field"
          :min="1"
          :max="maxPartySize"
          required
        />
      </div>

      <button
        type="submit"
        :disabled="!isValid"
        class="w-full rounded-2xl px-5 py-3 text-sm font-semibold text-white transition-opacity disabled:opacity-40"
        :style="{ background: accentColour }"
      >
        Continuer
      </button>
    </form>
  </div>
</template>
