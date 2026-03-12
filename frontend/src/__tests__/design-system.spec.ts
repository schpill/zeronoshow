import { describe, expect, it } from 'vitest'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

import tailwindConfig from '../../tailwind.config'

describe('design system', () => {
  it('declares typography utility classes', () => {
    const css = readFileSync(resolve(process.cwd(), 'src/assets/app.css'), 'utf8')

    expect(css).toContain('.text-heading-1')
    expect(css).toContain('@apply text-4xl font-extrabold')
    expect(css).toContain('.text-label')
    expect(css).toContain('@apply text-sm font-medium')
    expect(css).toContain('.text-overline')
    expect(css).toContain('uppercase tracking-widest')
  })

  it('defines brand palette and dark mode in tailwind config', () => {
    expect(tailwindConfig.darkMode).toBe('class')
    expect(tailwindConfig.theme.extend.fontFamily.sans[0]).toBe('Inter')
    expect(tailwindConfig.theme.extend.colors.brand[500]).toBe('#10B981')
  })
})
