import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import TopicPage from '@/components/TopicPage.vue'
import { createRouter, createWebHistory } from 'vue-router'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

vi.mock('@/services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    defaults: { headers: { common: {} } },
  },
}))

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/forum/:id', name: 'topic', component: TopicPage },
    { path: '/forum', name: 'forum', component: { template: '<div>Forum</div>' } },
    { path: '/login', name: 'login', component: { template: '<div>Login</div>' } },
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
    { id: 1, content: 'Post 1\nline 2', created_at: '2024-01-01T10:00:00Z', user: { id: 10, username: 'User1' }, vote_balance: 5, user_vote: null },
    { id: 2, content: 'Post 2', created_at: '2024-01-02T10:00:00Z', user: { id: 20, username: 'User2' }, vote_balance: 0, user_vote: null },
    { id: 3, content: 'Post 3', created_at: '2024-01-03T10:00:00Z', user: { id: 30, username: 'User3' }, vote_balance: -2, user_vote: null },
  ],
  meta: { current_page: 1, last_page: 1, per_page: 10, total: 3 },
}

function createAuthPinia() {
  const pinia = createPinia()
  setActivePinia(pinia)
  const auth = useAuthStore()
  auth.setToken('valid-token')
  auth.user = { id: 1, username: 'TestUser' }
  return pinia
}

describe('TopicPage', () => {
  beforeEach(async () => {
    vi.clearAllMocks()
    localStorage.clear()
    api.get.mockResolvedValueOnce({ data: structuredClone(mockTopic) })
    api.get.mockResolvedValueOnce({ data: structuredClone(mockPostsResponse) })
    router.push('/forum/1')
    await router.isReady()
  })

  it('fetches topic and posts on mount', async () => {
    const pinia = createAuthPinia()
    mount(TopicPage, { global: { plugins: [router, pinia] } })
    await flushPromises()
    expect(api.get).toHaveBeenCalledWith('/forums/1')
    expect(api.get).toHaveBeenCalledWith('/forums/1/posts', { params: { page: 1, per_page: 10 } })
  })

  it('renders topic title and metadata', async () => {
    const pinia = createAuthPinia()
    const wrapper = mount(TopicPage, { global: { plugins: [router, pinia] } })
    await flushPromises()
    expect(wrapper.text()).toContain('Test Topic')
    expect(wrapper.text()).toContain('Dans: General')
    expect(wrapper.text()).toContain('Par: Author')
  })

  it('renders posts', async () => {
    const pinia = createAuthPinia()
    const wrapper = mount(TopicPage, { global: { plugins: [router, pinia] } })
    await flushPromises()
    const posts = wrapper.findAll('.post')
    expect(posts).toHaveLength(3)
    expect(posts.at(0).text()).toContain('User1')
    expect(posts.at(1).text()).toContain('User2')
  })

  it('shows loading state initially', () => {
    const pinia = createAuthPinia()
    const wrapper = mount(TopicPage, { global: { plugins: [router, pinia] } })
    expect(wrapper.text()).toContain('Chargement…')
  })

  it('formats content with br tags', async () => {
    const pinia = createAuthPinia()
    const wrapper = mount(TopicPage, { global: { plugins: [router, pinia] } })
    await flushPromises()
    const content = wrapper.vm.formatContent('Hello\nWorld')
    expect(content).toBe('Hello<br/>World')
  })

  it('formats dates', () => {
    const pinia = createAuthPinia()
    const wrapper = mount(TopicPage, { global: { plugins: [router, pinia] } })
    const date = wrapper.vm.formatDate('2024-06-15T10:30:00Z')
    expect(date).toBeTruthy()
  })

  it('handles page change', async () => {
    const pinia = createAuthPinia()
    const wrapper = mount(TopicPage, { global: { plugins: [router, pinia] } })
    await flushPromises()
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: { data: [], meta: { current_page: 2, last_page: 3, per_page: 10, total: 22 } } })
    wrapper.vm.page = 2
    wrapper.vm.onPageChange(2)
    await flushPromises()
    expect(wrapper.vm.page).toBe(2)
  })

  it('votes up on a post', async () => {
    const pinia = createAuthPinia()
    const wrapper = mount(TopicPage, { global: { plugins: [router, pinia] } })
    await flushPromises()
    api.post.mockResolvedValueOnce({ data: { vote_balance: 6, user_vote: 'up' } })
    await wrapper.find('.vote-btn').trigger('click')
    await flushPromises()
    expect(api.post).toHaveBeenCalledWith('/posts/1/vote', { vote: 'up' })
    expect(wrapper.vm.posts[0].vote_balance).toBe(6)
    expect(wrapper.vm.posts[0].user_vote).toBe('up')
  })

  it('votes down on a post', async () => {
    const pinia = createAuthPinia()
    const wrapper = mount(TopicPage, { global: { plugins: [router, pinia] } })
    await flushPromises()
    api.post.mockResolvedValueOnce({ data: { vote_balance: -1, user_vote: 'down' } })
    const downBtns = wrapper.findAll('.vote-btn')
    await downBtns.at(1).trigger('click')
    await flushPromises()
    expect(api.post).toHaveBeenCalledWith('/posts/1/vote', { vote: 'down' })
  })

  it('renders vote scores', async () => {
    const pinia = createAuthPinia()
    const wrapper = mount(TopicPage, { global: { plugins: [router, pinia] } })
    await flushPromises()
    const scores = wrapper.findAll('.vote-score')
    expect(scores.at(0).text()).toBe('5')
    expect(scores.at(1).text()).toBe('0')
    expect(scores.at(2).text()).toBe('-2')
  })

  it('shows reply form when authenticated', async () => {
    const pinia = createAuthPinia()
    const wrapper = mount(TopicPage, { global: { plugins: [router, pinia] } })
    await flushPromises()
    expect(wrapper.find('.reply-form').exists()).toBe(true)
    expect(wrapper.find('textarea').exists()).toBe(true)
  })

  it('submits a reply', async () => {
    const pinia = createAuthPinia()
    api.post.mockResolvedValueOnce({ data: { id: 4, content: 'My reply', user: { username: 'Me' }, vote_balance: 0, user_vote: null } })

    const wrapper = mount(TopicPage, { global: { plugins: [router, pinia] } })
    await flushPromises()
    wrapper.find('textarea').setValue('My reply')
    await wrapper.find('.reply-form').trigger('submit.prevent')
    await flushPromises()

    expect(api.post).toHaveBeenCalledWith('/forums/1/posts', { content: 'My reply' })
    expect(wrapper.vm.posts).toHaveLength(4)
    expect(wrapper.vm.replyContent).toBe('')
  })
})
