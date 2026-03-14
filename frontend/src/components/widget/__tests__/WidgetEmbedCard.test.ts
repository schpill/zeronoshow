import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import WidgetEmbedCard from '../WidgetEmbedCard.vue'

describe('WidgetEmbedCard', () => {
  const defaultProps = {
    embedUrl: 'http://localhost/widget/embed/tok-1',
    bookingUrl: 'http://localhost/widget/tok-1',
    accentColour: '#6366f1',
  }

  beforeEach(() => {
    vi.clearAllMocks()
    Object.defineProperty(navigator, 'clipboard', {
      value: { writeText: vi.fn() },
      writable: true,
      configurable: true,
    })
  })

  it('shows booking URL', () => {
    const wrapper = mount(WidgetEmbedCard, { props: defaultProps })

    expect(wrapper.text()).toContain('http://localhost/widget/tok-1')
    expect(wrapper.text()).toContain('Lien direct')
  })

  it('copy button copies URL to clipboard (mock navigator.clipboard)', async () => {
    const wrapper = mount(WidgetEmbedCard, { props: defaultProps })

    const copyBtn = wrapper.findAll('button').find((btn) => btn.text().includes('Copier le lien'))
    expect(copyBtn).toBeDefined()

    await copyBtn!.trigger('click')

    expect(navigator.clipboard.writeText).toHaveBeenCalledWith('http://localhost/widget/tok-1')
  })

  it('shows iframe code snippet', () => {
    const wrapper = mount(WidgetEmbedCard, { props: defaultProps })

    const expectedCode = `<iframe src="${defaultProps.embedUrl}" width="400" height="700" frameborder="0" title="Réservation en ligne"></iframe>`
    expect(wrapper.text()).toContain(expectedCode)
    expect(wrapper.text()).toContain('Code iframe')
  })
})
