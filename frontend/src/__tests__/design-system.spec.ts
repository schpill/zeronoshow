import { describe, expect, it } from 'vitest'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

import tailwindConfig from '../../tailwind.config'

describe('design system', () => {
  it('declares typography utility classes', () => {
    const css = readFileSync(resolve(process.cwd(), 'src/assets/app.css'), 'utf8')

    expect(css).toContain('.text-heading-1')
    expect(css).toContain('font-size: 2.25rem;')
    expect(css).toContain('font-weight: 800;')
    expect(css).toContain('.text-label')
    expect(css).toContain('font-size: 0.875rem;')
    expect(css).toContain('font-weight: 500;')
    expect(css).toContain('.text-overline')
    expect(css).toContain('text-transform: uppercase;')
    expect(css).toContain('letter-spacing: 0.16em;')
  })

  it('defines brand palette and dark mode in tailwind config', () => {
    expect(tailwindConfig.darkMode).toBe('class')
    expect(tailwindConfig.theme.extend.fontFamily.sans[0]).toBe('Inter')
    expect(tailwindConfig.theme.extend.colors.brand[500]).toBe('#10B981')
  })
})
