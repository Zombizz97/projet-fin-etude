import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import Navbar from '@/components/Navbar.vue'
import { createRouter, createWebHistory } from 'vue-router'
import api from '@/services/api'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: { template: '<div>Home</div>' } },
    { path: '/login', name: 'login', component: { template: '<div>Login</div>' } },
    { path: '/register', name: 'register', component: { template: '<div>Register</div>' } },
    { path: '/profile', name: 'profile', component: { template: '<div>Profile</div>' } },
    { path: '/forum', name: 'forum', component: { template: '<div>Forum</div>' } },
    { path: '/players', name: 'players', component: { template: '<div>Players</div>' } },
  ],
})

function factory() {
  return mount(Navbar, {
    global: {
      plugins: [router],
    },
  })
}

describe('Navbar', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    localStorage.clear()
    router.push('/')
    await router.isReady()
  })

  it('renders desktop nav links', () => {
    const wrapper = factory()
    const links = wrapper.findAll('.navlink')
    expect(links).toHaveLength(3)
    expect(links.at(0).text()).toBe('Acceuil')
    expect(links.at(1).text()).toBe('Forum')
    expect(links.at(2).text()).toBe('Joueurs')
  })

  it('shows login and register when not authenticated', () => {
    const wrapper = factory()
    const btns = wrapper.findAll('.navbar-right .btn')
    expect(btns.at(0).text()).toBe('Se connecter')
    expect(btns.at(1).text()).toBe('Créer un compte')
  })

  it('shows user info and logout when authenticated', async () => {
    const store = useAuthStore()
    store.user = { id: 1, username: 'TestUser', avatarUrl: null }
    store.setToken('jwt')
    api.get.mockResolvedValue({ data: { user: { id: 1, username: 'TestUser', avatarUrl: null } } })

    const wrapper = factory()
    await wrapper.vm.$nextTick()

    expect(wrapper.find('.pseudo').text()).toBe('TestUser')
    expect(wrapper.find('.navbar-right').text()).toContain('Se déconnecter')
  })

  it('calls logout and redirects on logout click', async () => {
    const store = useAuthStore()
    store.user = { id: 1, username: 'TestUser' }
    store.setToken('jwt')
    api.get.mockResolvedValue({ data: { user: { id: 1, username: 'TestUser' } } })
    const logoutSpy = vi.spyOn(store, 'logout')

    const wrapper = factory()
    await wrapper.vm.$nextTick()

    const logoutBtn = wrapper.findAll('.navbar-right button').at(0)
    await logoutBtn.trigger('click')

    expect(logoutSpy).toHaveBeenCalled()
  })

  it('toggles mobile menu', async () => {
    const wrapper = factory()
    expect(wrapper.vm.isOpen).toBe(false)

    const burger = wrapper.find('.burger')
    await burger.trigger('click')
    expect(wrapper.vm.isOpen).toBe(true)

    await burger.trigger('click')
    expect(wrapper.vm.isOpen).toBe(false)
  })

  it('closes mobile menu on escape key', async () => {
    const wrapper = factory()
    wrapper.vm.isOpen = true

    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
    expect(wrapper.vm.isOpen).toBe(false)
  })
})
