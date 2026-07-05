import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

vi.mock('@/services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    defaults: { headers: { common: {} } },
  },
}))

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
  })

  it('starts with no token and not authenticated', () => {
    const store = useAuthStore()
    expect(store.token).toBe('')
    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
  })

  it('setToken stores token and sets auth header', () => {
    const store = useAuthStore()
    store.setToken('my-token')
    expect(store.token).toBe('my-token')
    expect(store.isAuthenticated).toBe(true)
    expect(localStorage.setItem).toHaveBeenCalledWith('token', 'my-token')
    expect(api.defaults.headers.common['Authorization']).toBe('Bearer my-token')
  })

  it('setToken with empty removes token', () => {
    const store = useAuthStore()
    store.setToken('my-token')
    store.setToken('')
    expect(store.token).toBe('')
    expect(store.isAuthenticated).toBe(false)
    expect(localStorage.removeItem).toHaveBeenCalledWith('token')
  })

  it('login sets token and user', async () => {
    const store = useAuthStore()
    api.post.mockResolvedValue({ data: { token: 'jwt', user: { id: 1, username: 'test' } } })

    await store.login({ username: 'test', password: 'pass' })

    expect(store.token).toBe('jwt')
    expect(store.user).toEqual({ id: 1, username: 'test' })
    expect(store.isAuthenticated).toBe(true)
    expect(api.post).toHaveBeenCalledWith('/auth/login', { username: 'test', password: 'pass' })
  })

  it('logout clears token and user', () => {
    const store = useAuthStore()
    store.setToken('jwt')
    store.user = { id: 1 }
    store.logout()
    expect(store.token).toBe('')
    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
  })

  it('fetchMe with token returns user', async () => {
    const store = useAuthStore()
    store.setToken('jwt')
    api.get.mockResolvedValue({ data: { user: { id: 1, username: 'test' } } })

    const user = await store.fetchMe()
    expect(user).toEqual({ id: 1, username: 'test' })
    expect(store.user).toEqual({ id: 1, username: 'test' })
  })

  it('fetchMe without token returns null', async () => {
    const store = useAuthStore()
    const result = await store.fetchMe()
    expect(result).toBeNull()
  })

  it('fetchMe on error clears token and user', async () => {
    const store = useAuthStore()
    store.setToken('jwt')
    store.user = { id: 1 }
    api.get.mockRejectedValue(new Error('Network error'))

    const result = await store.fetchMe()
    expect(result).toBeNull()
    expect(store.token).toBe('')
    expect(store.user).toBeNull()
  })
})
