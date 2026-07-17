import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import TopicPage from '@/components/TopicPage.vue'
import { createRouter, createWebHistory } from 'vue-router'
import { createPinia } from 'pinia'
import api from '@/services/api'

vi.mock('@/services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    defaults: { headers: { common: {} } },
  },
}))

const pinia = createPinia()

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/forum/:id', name: 'topic', component: TopicPage },
    { path: '/forum', name: 'forum', component: { template: '<div>Forum</div>' } },
  ],
})

const mockTopic = {
  id: 1,
  title: 'Test Topic',
  is_archived: false,
  posts_count: 3,
  category: { id: 1, name: 'General' },
  user: { id: 1, username: 'Author' },
}

const mockPostsResponse = {
  data: [
    { id: 1, content: 'Post 1\nline 2', created_at: '2024-01-01T10:00:00Z', user: { username: 'User1' } },
    { id: 2, content: 'Post 2', created_at: '2024-01-02T10:00:00Z', user: { username: 'User2' } },
    { id: 3, content: 'Post 3', created_at: '2024-01-03T10:00:00Z', user: { username: 'User3' } },
  ],
  meta: { current_page: 1, last_page: 1, per_page: 10, total: 3 },
}

describe('TopicPage', () => {
  beforeEach(async () => {
    vi.clearAllMocks()
    api.get.mockResolvedValueOnce({ data: mockTopic })
    api.get.mockResolvedValueOnce({ data: mockPostsResponse })
    router.push('/forum/1')
    await router.isReady()
  })

  it('fetches topic and posts on mount', async () => {
    mount(TopicPage, { global: { plugins: [router, pinia] } })
    await flushPromises()
    expect(api.get).toHaveBeenCalledWith('/forums/1')
    expect(api.get).toHaveBeenCalledWith('/forums/1/posts', { params: { page: 1, per_page: 10 } })
  })

  it('renders topic title and metadata', async () => {
    const wrapper = mount(TopicPage, { global: { plugins: [router, pinia] } })
    await flushPromises()
    expect(wrapper.text()).toContain('Test Topic')
    expect(wrapper.text()).toContain('Dans: General')
    expect(wrapper.text()).toContain('Par: Author')
  })

  it('renders posts', async () => {
    const wrapper = mount(TopicPage, { global: { plugins: [router, pinia] } })
    await flushPromises()
    const posts = wrapper.findAll('.post')
    expect(posts).toHaveLength(3)
    expect(posts.at(0).text()).toContain('User1')
    expect(posts.at(1).text()).toContain('User2')
  })

  it('shows loading state initially', () => {
    const wrapper = mount(TopicPage, { global: { plugins: [router, pinia] } })
    expect(wrapper.text()).toContain('Chargement…')
  })

  it('formats content with br tags', async () => {
    const wrapper = mount(TopicPage, { global: { plugins: [router, pinia] } })
    await flushPromises()
    const content = wrapper.vm.formatContent('Hello\nWorld')
    expect(content).toBe('Hello<br/>World')
  })

  it('formats dates', () => {
    const wrapper = mount(TopicPage, { global: { plugins: [router, pinia] } })
    const date = wrapper.vm.formatDate('2024-06-15T10:30:00Z')
    expect(date).toBeTruthy()
  })

  it('handles page change', async () => {
    const wrapper = mount(TopicPage, { global: { plugins: [router, pinia] } })
    await flushPromises()
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: { data: [], meta: { current_page: 2, last_page: 3, per_page: 10, total: 22 } } })
    wrapper.vm.page = 2
    wrapper.vm.onPageChange(2)
    await flushPromises()
    expect(wrapper.vm.page).toBe(2)
  })
})
