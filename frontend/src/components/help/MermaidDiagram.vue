<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'

const props = defineProps<{
  definition: string
}>()

const container = ref<HTMLDivElement | null>(null)
const error = ref<string | null>(null)

async function renderDiagram() {
  if (!container.value || !props.definition) return

  error.value = null

  try {
    const { default: mermaid } = await import('mermaid')
    mermaid.initialize({
      startOnLoad: false,
      theme: 'default',
      securityLevel: 'loose',
    })

    const id = `mermaid-${Math.random().toString(36).slice(2, 9)}`
    const { svg } = await mermaid.render(id, props.definition)
    container.value.innerHTML = svg
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Erreur de rendu du diagramme'
  }
}

onMounted(() => {
  void renderDiagram()
})

watch(() => props.definition, () => {
  void renderDiagram()
})
</script>

<template>
  <div
    v-if="error"
    class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
  >
    {{ error }}
  </div>
  <div ref="container" class="overflow-x-auto" />
</template>
