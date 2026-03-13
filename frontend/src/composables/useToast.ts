import { computed, ref } from 'vue'

export type ToastType = 'success' | 'error' | 'warning'

export interface ToastOptions {
  duration?: number
}

export interface ToastRecord {
  id: string
  type: ToastType
  message: string
  duration: number
}

const toasts = ref<ToastRecord[]>([])

function createToast(type: ToastType, message: string, options: ToastOptions = {}) {
  const toast: ToastRecord = {
    id: `${type}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    type,
    message,
    duration: options.duration ?? 4000,
  }

  toasts.value = [...toasts.value, toast]

  if (toast.duration > 0) {
    window.setTimeout(() => {
      dismiss(toast.id)
    }, toast.duration)
  }

  return toast
}

function dismiss(id: string) {
  toasts.value = toasts.value.filter((toast) => toast.id !== id)
}

export function resetToastsForTests() {
  toasts.value = []
}

export function useToast() {
  return {
    toasts: computed(() => toasts.value),
    success: (message: string, options?: ToastOptions) => createToast('success', message, options),
    error: (message: string, options?: ToastOptions) => createToast('error', message, options),
    warning: (message: string, options?: ToastOptions) => createToast('warning', message, options),
    dismiss,
  }
}
