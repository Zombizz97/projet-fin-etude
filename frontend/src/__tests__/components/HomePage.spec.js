import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import HomePage from '@/components/HomePage.vue'
import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: { template: '<div>Home</div>' } },
    { path: '/register', name: 'register', component: { template: '<div>Register</div>' } },
    { path: '/profile', name: 'profile', component: { template: '<div>Profile</div>' } },
    { path: '/forum', name: 'forum', component: { template: '<div>Forum</div>' } },
  ],
})

describe('HomePage', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    localStorage.clear()
    router.push('/')
    await router.isReady()
  })

  it('renders title and subtitle', () => {
    const wrapper = mount(HomePage, { global: { plugins: [router] } })
    expect(wrapper.text()).toContain('Bienvenue sur SmashConnect')
    expect(wrapper.text()).toContain('Rejoignez la communauté')
  })

  it('shows register CTA when not authenticated', () => {
    const wrapper = mount(HomePage, { global: { plugins: [router] } })
    const links = wrapper.findAll('.hero-actions a')
    expect(links.at(0).text()).toBe('Créer un compte')
    expect(links.at(0).attributes('href')).toBe('/register')
  })

  it('shows profile CTA when authenticated', async () => {
    const store = useAuthStore()
    store.user = { id: 1, username: 'test' }
    store.setToken('jwt')

    const wrapper = mount(HomePage, { global: { plugins: [router] } })
    await wrapper.vm.$nextTick()

    const links = wrapper.findAll('.hero-actions a')
    expect(links.at(0).text()).toBe('Profil')
    expect(links.at(0).attributes('href')).toBe('/profile')
  })

  it('always shows forum link', () => {
    const wrapper = mount(HomePage, { global: { plugins: [router] } })
    const links = wrapper.findAll('.hero-actions a')
    expect(links.at(1).text()).toBe('Accéder au forum')
  })
})
