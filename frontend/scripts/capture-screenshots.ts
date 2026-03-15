import { chromium } from 'playwright'
import * as fs from 'fs'
import * as path from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

const BASE_URL = process.env.BASE_URL || 'http://nginx'
const SCREENSHOT_DIR = path.resolve(__dirname, '../public/docs/screenshots')

interface PageToCapture {
  name: string
  url: string
  waitForSelector?: string
}

async function main() {
  fs.mkdirSync(SCREENSHOT_DIR, { recursive: true })

  const browser = await chromium.launch({ args: ['--no-sandbox'] })
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    locale: 'fr-FR',
  })
  const page = await context.newPage()

  // Login
  console.log('Logging in...')
  try {
    const loginRes = await fetch(`${BASE_URL}/api/v1/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: 'demo@zeronoshow.fr', password: 'password123' }),
    })
    if (!loginRes.ok) {
      console.warn('Login failed — taking public-only screenshots')
    } else {
      const { token } = await loginRes.json()
      await context.addCookies([
        {
          name: 'znz_token',
          value: token,
          domain: new URL(BASE_URL).hostname,
          path: '/',
        },
      ])
    }
  } catch {
    console.warn('Cannot reach API — taking public-only screenshots')
  }

  const pages: PageToCapture[] = [
    { name: 'dashboard-overview', url: '/dashboard', waitForSelector: 'main' },
    { name: 'help-index', url: '/help', waitForSelector: 'h1' },
    { name: 'help-reservations', url: '/help/reservations', waitForSelector: 'h1' },
    { name: 'help-sms', url: '/help/sms', waitForSelector: 'h1' },
    { name: 'help-scoring', url: '/help/scoring', waitForSelector: 'h1' },
    { name: 'help-widget', url: '/help/widget', waitForSelector: 'h1' },
    { name: 'help-waitlist', url: '/help/waitlist', waitForSelector: 'h1' },
    { name: 'help-customers', url: '/help/customers', waitForSelector: 'h1' },
    { name: 'help-reputation', url: '/help/reputation', waitForSelector: 'h1' },
    { name: 'help-leo', url: '/help/leo', waitForSelector: 'h1' },
  ]

  for (const p of pages) {
    try {
      await page.goto(`${BASE_URL}${p.url}`, { waitUntil: 'networkidle', timeout: 15000 })
      if (p.waitForSelector) {
        await page.waitForSelector(p.waitForSelector, { timeout: 5000 }).catch(() => {})
      }
      await page.screenshot({ path: path.join(SCREENSHOT_DIR, `${p.name}.png`), fullPage: true })
      console.log(`✓ ${p.name}.png`)
    } catch (err) {
      console.warn(`✗ ${p.name}: ${err instanceof Error ? err.message : 'unknown error'}`)
    }
  }

  await browser.close()
  console.log(`Done. Screenshots saved to ${SCREENSHOT_DIR}`)
}

main().catch(console.error)
