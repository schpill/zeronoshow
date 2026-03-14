<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { sendOtp, verifyOtp } from '@/api/widget'

const props = defineProps<{
  businessToken: string
  phone: string
  accentColour: string
}>()

const emit = defineEmits<{
  verified: [guestToken: string]
}>()

const digits = ref<string[]>(['', '', '', '', '', ''])
const inputs = ref<HTMLInputElement[]>([])
const error = ref<string | null>(null)
const verifying = ref(false)
const sending = ref(false)
const resendCooldown = ref(0)
let resendTimer: ReturnType<typeof setInterval> | null = null

onMounted(async () => {
  await sendCode()
})

async function sendCode() {
  sending.value = true
  error.value = null
  try {
    await sendOtp(props.businessToken, props.phone)
    startResendCooldown()
  } catch (err: unknown) {
    const e = err as { data?: { error?: { message?: string } } }
    error.value = e.data?.error?.message ?? "Erreur lors de l'envoi du code."
  } finally {
    sending.value = false
  }
}

function startResendCooldown() {
  resendCooldown.value = 60
  if (resendTimer) clearInterval(resendTimer)
  resendTimer = setInterval(() => {
    resendCooldown.value--
    if (resendCooldown.value <= 0 && resendTimer) {
      clearInterval(resendTimer)
      resendTimer = null
    }
  }, 1000)
}

async function onDigitInput(index: number, event: Event) {
  const input = event.target as HTMLInputElement
  const value = input.value.replace(/\D/g, '').slice(-1)
  digits.value[index] = value

  if (value && index < 5) {
    await nextTick()
    inputs.value[index + 1]?.focus()
  }

  if (digits.value.every((d) => d !== '')) {
    await verify()
  }
}

async function onKeydown(index: number, event: KeyboardEvent) {
  if (event.key === 'Backspace' && !digits.value[index] && index > 0) {
    inputs.value[index - 1]?.focus()
  }
}

async function verify() {
  const code = digits.value.join('')
  if (code.length !== 6) return

  verifying.value = true
  error.value = null

  try {
    const response = await verifyOtp(props.businessToken, props.phone, code)
    emit('verified', response.guest_token)
  } catch (err: unknown) {
    const e = err as { data?: { error?: { message?: string } } }
    error.value = e.data?.error?.message ?? 'Code incorrect.'
    digits.value = ['', '', '', '', '', '']
    await nextTick()
    inputs.value[0]?.focus()
  } finally {
    verifying.value = false
  }
}
</script>

<template>
  <div>
    <h3 class="text-heading-4 mb-2">Vérification</h3>
    <p class="text-body-sm mb-6">
      Un code a été envoyé par SMS au {{ phone }}.
    </p>

    <div class="flex justify-center gap-2 mb-4">
      <input
        v-for="(_, i) in digits"
        :key="i"
        :ref="(el) => { if (el) inputs[i] = el as HTMLInputElement }"
        type="text"
        inputmode="numeric"
        maxlength="1"
        class="h-12 w-10 rounded-xl border border-slate-300 text-center text-lg font-semibold text-slate-800 focus:border-slate-500 focus:outline-none"
        :style="{ borderColor: digits[i] ? accentColour : undefined }"
        :value="digits[i]"
        @input="onDigitInput(i, $event)"
        @keydown="onKeydown(i, $event)"
      />
    </div>

    <p v-if="error" class="mb-4 text-center text-sm text-red-600">{{ error }}</p>

    <div v-if="verifying" class="text-center text-sm text-slate-500">Vérification en cours...</div>

    <button
      type="button"
      :disabled="resendCooldown > 0 || sending"
      class="mx-auto block text-sm font-medium disabled:opacity-40"
      :style="{ color: accentColour }"
      @click="sendCode"
    >
      <span v-if="sending">Envoi en cours...</span>
      <span v-else-if="resendCooldown > 0">Renvoyer le code ({{ resendCooldown }}s)</span>
      <span v-else>Renvoyer le code</span>
    </button>
  </div>
</template>
