import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'

import { adminApiClient } from '@/api/adminAxios'
import AdminAuditPage from '@/pages/admin/AdminAuditPage.vue'

vi.mock('@/api/adminAxios', () => ({
  adminApiClient: {
    get: vi.fn(),
  },
}))

const mountPage = () =>
  mount(AdminAuditPage, {
    global: {
      stubs: {
        RouterLink: { props: ['to'], template: '<a :href="to"><slot /></a>' },
      },
    },
  })

describe('AdminAuditPage', () => {
  beforeEach(() => {
    vi.mocked(adminApiClient.get).mockReset()
    vi.mocked(adminApiClient.get)
      .mockResolvedValueOnce({
        data: [
          {
            id: 'log-1',
            created_at: '2026-03-15T12:00:00Z',
            admin_name: 'Gerald',
            action: 'impersonate',
            target_type: 'Business',
            target_id: 'biz-1',
            payload: { business_name: 'Alpha Bistro' },
          },
        ],
        meta: { current_page: 1, last_page: 2, total: 51 },
      })
      .mockResolvedValueOnce({
        data: [],
        meta: { current_page: 2, last_page: 2, total: 51 },
      })
  })

  it('renders filters and a clickable business target link', async () => {
    const wrapper = mountPage()

    await Promise.resolve()
    await Promise.resolve()

    expect(wrapper.find('input[type="date"]').exists()).toBe(true)
    expect(wrapper.html()).toContain('/admin/businesses/biz-1')
    expect(wrapper.text()).toContain('Alpha Bistro')
  })

  it('builds action and date filters into the query string', async () => {
    const wrapper = mountPage()
    await Promise.resolve()
    await Promise.resolve()

    const inputs = wrapper.findAll('input[type="date"]')
    await wrapper.get('select').setValue('impersonate')
    await inputs[0]!.setValue('2026-03-10')
    await inputs[1]!.setValue('2026-03-15')
    await wrapper.get('[data-testid="apply-audit-filters"]').trigger('click')

    expect(vi.mocked(adminApiClient.get).mock.calls[1]?.[0]).toContain('action=impersonate')
    expect(vi.mocked(adminApiClient.get).mock.calls[1]?.[0]).toContain('date_from=2026-03-10')
    expect(vi.mocked(adminApiClient.get).mock.calls[1]?.[0]).toContain('date_to=2026-03-15')
  })

  it('loads the next page when pagination is used', async () => {
    const wrapper = mountPage()
    await Promise.resolve()
    await Promise.resolve()

    await wrapper.get('[data-testid="audit-next-page"]').trigger('click')

    expect(vi.mocked(adminApiClient.get).mock.calls[1]?.[0]).toContain('page=2')
  })
})
