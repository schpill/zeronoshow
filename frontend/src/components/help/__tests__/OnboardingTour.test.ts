import { describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'

import OnboardingTour from '@/components/help/OnboardingTour.vue'
import type { TourStep } from '@/components/help/OnboardingTour.vue'

const mockSteps: TourStep[] = [
  { title: 'Step 1', body: 'Body 1', targetSelector: '#target-1' },
  { title: 'Step 2', body: 'Body 2', targetSelector: '#target-2' },
  { title: 'Step 3', body: 'Body 3', targetSelector: '#target-3' },
]

describe('OnboardingTour', () => {
  it('does not render when modelValue is false', () => {
    const wrapper = mount(OnboardingTour, {
      props: { modelValue: false, steps: mockSteps },
      global: { stubs: { Transition: false } },
    })

    expect(wrapper.find('[role="tooltip"]').exists()).toBe(false)
  })

  it('renders step 1 when visible', async () => {
    const wrapper = mount(OnboardingTour, {
      props: { modelValue: true, steps: mockSteps },
      global: { stubs: { Transition: false, Teleport: true } },
    })

    await nextTick()

    expect(wrapper.text()).toContain('Step 1')
    expect(wrapper.text()).toContain('Body 1')
    expect(wrapper.text()).toContain('1 / 3')
  })

  it('next button advances to step 2', async () => {
    const wrapper = mount(OnboardingTour, {
      props: { modelValue: true, steps: mockSteps },
      global: { stubs: { Transition: false, Teleport: true } },
    })

    await nextTick()

    const nextBtn = wrapper.findAll('button').find((b) => b.text().includes('Suivant'))
    expect(nextBtn).toBeTruthy()
    await nextBtn!.trigger('click')
    await nextTick()

    expect(wrapper.text()).toContain('Step 2')
    expect(wrapper.text()).toContain('2 / 3')
  })

  it('skip button emits skip event', async () => {
    const wrapper = mount(OnboardingTour, {
      props: { modelValue: true, steps: mockSteps },
      global: { stubs: { Transition: false, Teleport: true } },
    })

    await nextTick()

    const skipBtn = wrapper.findAll('button').find((b) => b.text().includes('Passer'))
    expect(skipBtn).toBeTruthy()
    await skipBtn!.trigger('click')

    expect(wrapper.emitted('skip')).toBeTruthy()
  })

  it('last step shows Termitter button and emits complete', async () => {
    const wrapper = mount(OnboardingTour, {
      props: { modelValue: true, steps: mockSteps },
      global: { stubs: { Transition: false, Teleport: true } },
    })

    await nextTick()

    // Navigate to last step
    const nextBtn1 = wrapper.findAll('button').find((b) => b.text().includes('Suivant'))
    await nextBtn1!.trigger('click')
    await nextTick()
    const nextBtn2 = wrapper.findAll('button').find((b) => b.text().includes('Suivant'))
    await nextBtn2!.trigger('click')
    await nextTick()

    const finishBtn = wrapper.findAll('button').find((b) => b.text().includes('Terminer'))
    expect(finishBtn).toBeTruthy()
    await finishBtn!.trigger('click')

    expect(wrapper.emitted('complete')).toBeTruthy()
  })
})
