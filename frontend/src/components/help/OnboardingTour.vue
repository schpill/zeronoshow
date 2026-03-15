<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'

export interface TourStep {
  title: string
  body: string
  targetSelector: string
  placement?: 'top' | 'bottom' | 'left' | 'right'
}

const props = defineProps<{
  modelValue: boolean
  steps: TourStep[]
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  complete: []
  skip: []
}>()

const currentStep = ref(0)
const targetRect = ref<DOMRect | null>(null)

const step = computed(() => props.steps[currentStep.value])
const isFirst = computed(() => currentStep.value === 0)
const isLast = computed(() => currentStep.value === props.steps.length - 1)
const progress = computed(() => `${currentStep.value + 1} / ${props.steps.length}`)

function findTarget(): HTMLElement | null {
  if (!step.value) return null
  return document.querySelector(step.value.targetSelector) as HTMLElement | null
}

async function updateRect() {
  await nextTick()
  const el = findTarget()
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' })
    await nextTick()
    targetRect.value = el.getBoundingClientRect()
  } else {
    targetRect.value = null
  }
}

watch(
  () => props.modelValue,
  (val) => {
    if (val) {
      currentStep.value = 0
      void updateRect()
    }
  },
)

watch(currentStep, () => {
  void updateRect()
})

function next() {
  if (isLast.value) {
    emit('complete')
  } else {
    currentStep.value++
  }
}

function previous() {
  if (!isFirst.value) {
    currentStep.value--
  }
}

function skip() {
  emit('skip')
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'ArrowRight' || e.key === 'Enter') {
    next()
  } else if (e.key === 'ArrowLeft') {
    previous()
  } else if (e.key === 'Escape') {
    skip()
  }
}

onMounted(() => {
  window.addEventListener('keydown', onKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown)
})

const overlayStyle = computed(() => {
  if (!targetRect.value) return {}
  const r = targetRect.value
  return {
    top: `${r.top - 8}px`,
    left: `${r.left - 8}px`,
    width: `${r.width + 16}px`,
    height: `${r.height + 16}px`,
  }
})

const popoverStyle = computed(() => {
  if (!targetRect.value) return { top: '50%', left: '50%', transform: 'translate(-50%, -50%)' }
  const r = targetRect.value
  const placement = step.value?.placement ?? 'bottom'
  if (placement === 'bottom') {
    return {
      top: `${r.bottom + 16}px`,
      left: `${r.left + r.width / 2}px`,
      transform: 'translateX(-50%)',
    }
  }
  if (placement === 'top') {
    return {
      top: `${r.top - 16}px`,
      left: `${r.left + r.width / 2}px`,
      transform: 'translate(-50%, -100%)',
    }
  }
  if (placement === 'left') {
    return {
      top: `${r.top + r.height / 2}px`,
      left: `${r.left - 16}px`,
      transform: 'translate(-100%, -50%)',
    }
  }
  return {
    top: `${r.top + r.height / 2}px`,
    left: `${r.right + 16}px`,
    transform: 'translateY(-50%)',
  }
})
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="modelValue" class="fixed inset-0 z-[9999]">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-950/50" @click="skip" />

        <!-- Spotlight overlay -->
        <div
          class="absolute rounded-2xl ring-4 ring-emerald-400/60 transition-all duration-300"
          :style="overlayStyle"
        />

        <!-- Popover -->
        <div
          class="absolute z-10 w-80 max-w-[calc(100vw-2rem)] rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
          :style="popoverStyle"
        >
          <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
            Étape {{ progress }}
          </p>
          <h3 class="mt-1 text-lg font-bold text-slate-900 dark:text-slate-50">
            {{ step?.title }}
          </h3>
          <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
            {{ step?.body }}
          </p>

          <div class="mt-4 flex items-center justify-between">
            <button
              type="button"
              class="text-sm font-medium text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-300"
              @click="skip"
            >
              Passer
            </button>

            <div class="flex items-center gap-2">
              <button
                v-if="!isFirst"
                type="button"
                class="rounded-xl border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                @click="previous"
              >
                ←
              </button>
              <button
                type="button"
                class="rounded-xl bg-emerald-600 px-4 py-1.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                @click="next"
              >
                {{ isLast ? 'Terminer' : 'Suivant →' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
