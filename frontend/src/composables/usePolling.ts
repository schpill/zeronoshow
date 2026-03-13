import { onMounted, onUnmounted, ref } from 'vue'

export function usePolling(fn: () => Promise<unknown>, intervalMs: number) {
  const isPolling = ref(false)
  let timer: number | null = null

  async function tick() {
    try {
      await fn()
    } catch {
      // Keep polling even when one refresh fails.
    }
  }

  function start() {
    if (timer !== null) {
      return
    }

    isPolling.value = true
    void tick()
    timer = window.setInterval(() => {
      void tick()
    }, intervalMs)
  }

  function stop() {
    if (timer !== null) {
      window.clearInterval(timer)
      timer = null
    }

    isPolling.value = false
  }

  onMounted(start)
  onUnmounted(stop)

  return {
    start,
    stop,
    isPolling,
  }
}
