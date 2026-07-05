import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createRouter, createWebHistory } from 'vue-router'
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

const HomePage = { template: '<div>Home</div>' }
const LoginPage = { template: '<div>Login</div>' }
const ProfilePage = { template: '<div>Profile</div>' }

describe('Router Guards', () => {
  let router

  beforeEach(async () => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
    router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/', name: 'home', component: HomePage },
        { path: '/login', name: 'login', component: LoginPage },
        { path: '/profile', name: 'profile', component: ProfilePage, meta: { requiresAuth: true } },
      ],
    })
    router.push('/')
    await router.isReady()
  })

  it('allows access to public routes', async () => {
    await router.push('/')
    expect(router.currentRoute.value.path).toBe('/')
  })

  it('redirects unauthenticated users from profile to login', async () => {
    await router.push('/profile')
    expect(router.currentRoute.value.path).toBe('/login')
    expect(router.currentRoute.value.query.redirect).toBe('/profile')
  })

  it('allows authenticated users to profile', async () => {
    const store = useAuthStore()
    store.setToken('jwt')
    store.user = { id: 1, username: 'test' }

    await router.push('/profile')
    expect(router.currentRoute.value.path).toBe('/profile')
  })

  it('calls fetchMe when token exists but not authenticated', async () => {
    localStorage.getItem.mockReturnValue('existing-token')
    api.get.mockResolvedValue({ data: { user: { id: 1, username: 'test' } } })

    router.push('/profile')
    await new Promise(r => setTimeout(r, 50))

    expect(api.get).toHaveBeenCalled()
  })
})
