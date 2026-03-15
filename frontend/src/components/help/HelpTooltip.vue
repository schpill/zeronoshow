<script setup lang="ts">
import { ref } from 'vue'

const props = withDefaults(
  defineProps<{
    content: string
    position?: 'top' | 'bottom' | 'left' | 'right'
    icon?: boolean
  }>(),
  {
    position: 'top',
    icon: true,
  },
)

const visible = ref(false)
const tooltipId = `tooltip-${Math.random().toString(36).slice(2, 9)}`

const positionClasses: Record<string, string> = {
  top: 'bottom-full left-1/2 -translate-x-1/2 mb-2',
  bottom: 'top-full left-1/2 -translate-x-1/2 mt-2',
  left: 'right-full top-1/2 -translate-y-1/2 mr-2',
  right: 'left-full top-1/2 -translate-y-1/2 ml-2',
}

function toggle() {
  visible.value = !visible.value
}

function show() {
  visible.value = true
}

function hide() {
  visible.value = false
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault()
    toggle()
  } else if (e.key === 'Escape') {
    hide()
  }
}
</script>

<template>
  <span class="relative inline-flex">
    <button
      type="button"
      :aria-describedby="tooltipId"
      role="button"
      class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-500 transition hover:bg-emerald-100 hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 dark:bg-slate-700 dark:text-slate-400 dark:hover:bg-emerald-900/40 dark:hover:text-emerald-400"
      @click="toggle"
      @mouseenter="show"
      @mouseleave="hide"
      @focus="show"
      @blur="hide"
      @keydown="onKeydown"
    >
      ?
    </button>

    <Transition
      enter-active-class="transition ease-out duration-150"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="visible"
        :id="tooltipId"
        role="tooltip"
        :class="positionClasses[position]"
        class="absolute z-50 w-64 rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-700 shadow-lg dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
      >
        {{ content }}
      </div>
    </Transition>
  </span>
</template>
