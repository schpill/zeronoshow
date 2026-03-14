import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useWaitlist } from '../useWaitlist'
import * as waitlistApi from '@/api/waitlist'

vi.mock('@/api/waitlist', () => ({
  getWaitlistEntries: vi.fn(),
  addWaitlistEntry: vi.fn(),
  removeWaitlistEntry: vi.fn(),
  reorderWaitlist: vi.fn(),
  notifyEntry: vi.fn(),
  getWaitlistSettings: vi.fn(),
  updateWaitlistSettings: vi.fn(),
  regeneratePublicLink: vi.fn(),
  getPublicWaitlistInfo: vi.fn(),
  joinWaitlistPublic: vi.fn(),
}))

describe('useWaitlist', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('fetches entries and updates state', async () => {
    const mockEntries = {
      data: [{ id: '1', client_name: 'Test' }],
      meta: { current_page: 1, last_page: 1, total: 1 },
    }
    vi.mocked(waitlistApi.getWaitlistEntries).mockResolvedValue(
      mockEntries /* eslint-disable-line @typescript-eslint/no-explicit-any */ as any,
    )

    const { entries, fetchEntries, loading } = useWaitlist()
    await fetchEntries()

    expect(loading.value).toBe(false)
    expect(entries.value).toHaveLength(1)
    expect(entries.value[0].client_name).toBe('Test')
    expect(entries.value[0]!.client_name).toBe('Test')
  })

  it('adds an entry successfully', async () => {
    const newEntry = { data: { id: '2', client_name: 'New' } }
    vi.mocked(waitlistApi.addWaitlistEntry).mockResolvedValue(
      newEntry /* eslint-disable-line @typescript-eslint/no-explicit-any */ as any,
    )

    const { entries, addEntry } = useWaitlist()
    await addEntry({
      slot_date: '2026-03-30',
      slot_time: '19:30',
      client_name: 'New',
      client_phone: '+336',
      party_size: 2,
    })

    expect(entries.value).toHaveLength(1)
    expect(entries.value[0].client_name).toBe('New')
    expect(entries.value[0]!.client_name).toBe('New')
  })

  it('removes an entry', async () => {
    const { entries, removeEntry } = useWaitlist()
    entries.value = [
      {
        id: '1',
        client_name: 'Test',
      } /* eslint-disable-line @typescript-eslint/no-explicit-any */ as any,
    ]

    vi.mocked(waitlistApi.removeWaitlistEntry).mockResolvedValue(undefined)

    await removeEntry('1')
    expect(entries.value).toHaveLength(0)
  })
})
