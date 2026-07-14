import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import ForumPage from '@/components/ForumPage.vue'
import { createRouter, createWebHistory } from 'vue-router'
import api from '@/services/api'

vi.mock('@/services/api', () => ({
  default: {
    get: vi.fn(),
    defaults: { headers: { common: {} } },
  },
}))

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/forum', name: 'forum', component: { template: '<div>Forum</div>' } },
  ],
})

const mockApiData = [
  {
    id: 1,
    name: 'General',
    topics: [
      { id: 1, title: 'Topic 1', is_archived: false, created_at: '2024-01-02T00:00:00Z', user: { username: 'User1' }, posts_count: 5 },
      { id: 2, title: 'Topic 2', is_archived: true, created_at: '2024-01-01T00:00:00Z', user: { username: 'User2' }, posts_count: 3 },
    ],
  },
]

describe('ForumPage', () => {
  beforeEach(async () => {
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: mockApiData })
    router.push('/forum')
    await router.isReady()
  })

  it('fetches forums on mount', async () => {
    mount(ForumPage, { global: { plugins: [router] } })
    await flushPromises()
    expect(api.get).toHaveBeenCalledWith('/forums')
  })

  it('renders topic cards', async () => {
    const wrapper = mount(ForumPage, { global: { plugins: [router] } })
    await flushPromises()
    const cards = wrapper.findAll('.card')
    expect(cards).toHaveLength(2)
    expect(cards.at(0).text()).toContain('Topic 1')
  })

  it('filters by text search', async () => {
    const wrapper = mount(ForumPage, { global: { plugins: [router] } })
    await flushPromises()
    const input = wrapper.find('.search input')
    await input.setValue('Topic 2')
    expect(wrapper.vm.filtered).toHaveLength(1)
    expect(wrapper.vm.filtered[0].title).toBe('Topic 2')
  })

  it('filters by state', async () => {
    const wrapper = mount(ForumPage, { global: { plugins: [router] } })
    await flushPromises()
    wrapper.vm.stateFilter = 'archived'
    expect(wrapper.vm.filtered).toHaveLength(1)
    expect(wrapper.vm.filtered[0].isArchived).toBe(true)
  })

  it('sorts by different fields', async () => {
    const wrapper = mount(ForumPage, { global: { plugins: [router] } })
    await flushPromises()
    wrapper.vm.sortBy = 'messagesCount'
    wrapper.vm.sortDir = 'desc'
    expect(wrapper.vm.sorted[0].messagesCount).toBe(5)
  })

  it('paginates results', async () => {
    const wrapper = mount(ForumPage, { global: { plugins: [router] } })
    await flushPromises()
    wrapper.vm.pageSize = 1
    expect(wrapper.vm.paged).toHaveLength(1)
    expect(wrapper.vm.totalPages).toBe(2)
  })

  it('shows empty state when no results', async () => {
    const wrapper = mount(ForumPage, { global: { plugins: [router] } })
    await flushPromises()
    const input = wrapper.find('.search input')
    await input.setValue('Nonexistent Topic')
    expect(wrapper.vm.filtered).toHaveLength(0)
    await wrapper.vm.$nextTick()
    expect(wrapper.text()).toContain("Aucun topic ne correspond à votre recherche.")
  })

  it('formats dates in French locale', () => {
    const wrapper = mount(ForumPage, { global: { plugins: [router] } })
    const date = wrapper.vm.formatDate('2024-06-15T10:30:00Z')
    expect(date).toContain('2024')
    expect(date).toContain('juin')
  })
})
