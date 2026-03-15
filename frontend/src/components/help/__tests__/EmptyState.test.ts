import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'

import EmptyState from '@/components/help/EmptyState.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [{ path: '/', component: { template: '<div />' } }],
})

describe('EmptyState', () => {
  it('renders icon, title and description', () => {
    const wrapper = mount(EmptyState, {
      props: {
        icon: '📋',
        title: 'Aucune réservation',
        description: 'Créez votre première réservation.',
      },
      global: { plugins: [router] },
    })

    expect(wrapper.text()).toContain('📋')
    expect(wrapper.text()).toContain('Aucune réservation')
    expect(wrapper.text()).toContain('Créez votre première réservation.')
  })

  it('renders action CTA as RouterLink when actionTo provided', () => {
    const wrapper = mount(EmptyState, {
      props: {
        icon: '📋',
        title: 'Aucune réservation',
        description: '',
        actionLabel: 'Créer une réservation',
        actionTo: '/dashboard#reservation-form',
      },
      global: { plugins: [router] },
    })

    const link = wrapper.find('a')
    expect(link.exists()).toBe(true)
    expect(link.text()).toBe('Créer une réservation')
    expect(link.attributes('href')).toBe('/dashboard#reservation-form')
  })

  it('does not render CTA when actionLabel is omitted', () => {
    const wrapper = mount(EmptyState, {
      props: {
        icon: '📋',
        title: 'Aucune réservation',
      },
      global: { plugins: [router] },
    })

    expect(wrapper.find('a').exists()).toBe(false)
  })
})
