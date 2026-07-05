import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import LoginPage from '@/components/LoginPage.vue'
import { createRouter, createWebHistory } from 'vue-router'
import api from '@/services/api'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: { template: '<div>Home</div>' } },
    { path: '/login', name: 'login', component: { template: '<div>Login</div>' } },
  ],
})

describe('LoginPage', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
    router.push('/login')
    await router.isReady()
  })

  it('renders login form', () => {
    const wrapper = mount(LoginPage, { global: { plugins: [router] } })
    expect(wrapper.find('#pseudo').exists()).toBe(true)
    expect(wrapper.find('#password').exists()).toBe(true)
    expect(wrapper.find('button[type="submit"]').text()).toBe('Se connecter')
  })

  it('shows validation errors on empty submit', async () => {
    const wrapper = mount(LoginPage, { global: { plugins: [router] } })
    await wrapper.find('form').trigger('submit')
    expect(wrapper.text()).toContain('Pseudo requis.')
    expect(wrapper.text()).toContain('Mot de passe requis.')
  })

  it('calls store.login on valid submit', async () => {
    const store = useAuthStore()
    vi.spyOn(store, 'login').mockResolvedValue({ token: 'jwt', user: { id: 1 } })
    const pushSpy = vi.spyOn(router, 'push')

    const wrapper = mount(LoginPage, { global: { plugins: [router] } })
    await wrapper.find('#pseudo').setValue('testuser')
    await wrapper.find('#password').setValue('password123')
    await wrapper.find('form').trigger('submit')

    expect(store.login).toHaveBeenCalledWith({ username: 'testuser', password: 'password123' })
    expect(pushSpy).toHaveBeenCalledWith('/')
  })

  it('shows api error on failed login', async () => {
    const store = useAuthStore()
    vi.spyOn(store, 'login').mockRejectedValue({
      response: { data: { message: 'Identifiants invalides' } },
    })

    const wrapper = mount(LoginPage, { global: { plugins: [router] } })
    await wrapper.find('#pseudo').setValue('testuser')
    await wrapper.find('#password').setValue('wrong')
    await wrapper.find('form').trigger('submit')
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('Identifiants invalides')
  })

  it('toggles password visibility', async () => {
    const wrapper = mount(LoginPage, { global: { plugins: [router] } })
    const passwordInput = wrapper.find('#password')
    expect(passwordInput.attributes('type')).toBe('password')

    await wrapper.find('.toggle-password').trigger('click')
    expect(passwordInput.attributes('type')).toBe('text')

    await wrapper.find('.toggle-password').trigger('click')
    expect(passwordInput.attributes('type')).toBe('password')
  })
})
