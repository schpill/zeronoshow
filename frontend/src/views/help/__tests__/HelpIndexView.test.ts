import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'

import HelpIndexView from '@/views/help/HelpIndexView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: { template: '<div />' } },
    { path: '/help', component: { template: '<div />' } },
    { path: '/help/:module', component: { template: '<div />' } },
    { path: '/dashboard', component: { template: '<div />' } },
  ],
})

describe('HelpIndexView', () => {
  beforeEach(() => {
    vi.stubGlobal('localStorage', {
      getItem: vi.fn(() => null),
      setItem: vi.fn(),
      removeItem: vi.fn(),
      clear: vi.fn(),
    })
    setActivePinia(createPinia())
  })

  it('renders all 8 module cards', () => {
    const wrapper = mount(HelpIndexView, {
      global: {
        plugins: [router],
        stubs: { RouterLink: { props: ['to'], template: '<a :href="to"><slot /></a>' } },
      },
    })

    expect(wrapper.text()).toContain('Réservations')
    expect(wrapper.text()).toContain('SMS')
    expect(wrapper.text()).toContain('Score de fiabilité')
    expect(wrapper.text()).toContain('Widget de réservation')
    expect(wrapper.text()).toContain("Liste d'attente")
    expect(wrapper.text()).toContain('Clients')
    expect(wrapper.text()).toContain('Réputation')
    expect(wrapper.text()).toContain('Léo — Assistant IA')
  })

  it('search input filters modules by title', async () => {
    const wrapper = mount(HelpIndexView, {
      global: {
        plugins: [router],
        stubs: { RouterLink: { props: ['to'], template: '<a :href="to"><slot /></a>' } },
      },
    })

    const input = wrapper.find('input[type="search"]')
    await input.setValue('SMS')

    expect(wrapper.text()).toContain('SMS')
    expect(wrapper.text()).not.toContain('Réservations')
    expect(wrapper.text()).not.toContain('Widget')
  })

  it('shows no results message when search has no match', async () => {
    const wrapper = mount(HelpIndexView, {
      global: {
        plugins: [router],
        stubs: { RouterLink: { props: ['to'], template: '<a :href="to"><slot /></a>' } },
      },
    })

    const input = wrapper.find('input[type="search"]')
    await input.setValue('xyznonexistent')

    expect(wrapper.text()).toContain('Aucun résultat')
  })

  it('search filters by keyword', async () => {
    const wrapper = mount(HelpIndexView, {
      global: {
        plugins: [router],
        stubs: { RouterLink: { props: ['to'], template: '<a :href="to"><slot /></a>' } },
      },
    })

    const input = wrapper.find('input[type="search"]')
    await input.setValue('telegram')

    expect(wrapper.text()).toContain('Léo')
    expect(wrapper.text()).not.toContain('Réservations')
  })
})
