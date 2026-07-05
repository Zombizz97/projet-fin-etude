import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import RegisterPage from '@/components/RegisterPage.vue'
import { createRouter, createWebHistory } from 'vue-router'
import api from '@/services/api'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: { template: '<div>Home</div>' } },
    { path: '/register', name: 'register', component: { template: '<div>Register</div>' } },
  ],
})

describe('RegisterPage', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: [] })
    router.push('/register')
    await router.isReady()
  })

  it('renders registration form', async () => {
    const wrapper = mount(RegisterPage, { global: { plugins: [router] } })
    await flushPromises()
    expect(wrapper.find('#pseudo').exists()).toBe(true)
    expect(wrapper.find('#password').exists()).toBe(true)
    expect(wrapper.find('#confirm').exists()).toBe(true)
    expect(wrapper.find('button[type="submit"]').text()).toBe('Créer le compte')
  })

  it('loads characters on mount', async () => {
    api.get.mockResolvedValue({
      data: [{ id: 1, name: 'Mario', icon_path: '/mario.png' }],
    })
    const wrapper = mount(RegisterPage, { global: { plugins: [router] } })
    await flushPromises()
    expect(wrapper.vm.characterOptions).toEqual([
      { id: 1, name: 'Mario', icon: '/mario.png' },
    ])
  })

  it('handles character load error', async () => {
    api.get.mockRejectedValue(new Error('Network error'))
    const wrapper = mount(RegisterPage, { global: { plugins: [router] } })
    await flushPromises()
    expect(wrapper.vm.characterOptions).toEqual([])
  })

  it('shows validation errors on empty submit', async () => {
    const wrapper = mount(RegisterPage, { global: { plugins: [router] } })
    await flushPromises()
    await wrapper.find('form').trigger('submit')
    expect(wrapper.vm.errors.pseudo).toBe('Pseudo requis.')
    expect(wrapper.vm.errors.password).toBe('Mot de passe requis.')
  })

  it('validates password minimum length', async () => {
    const wrapper = mount(RegisterPage, { global: { plugins: [router] } })
    await flushPromises()
    await wrapper.find('#pseudo').setValue('test')
    await wrapper.find('#password').setValue('12')
    await wrapper.find('#confirm').setValue('12')
    await wrapper.find('form').trigger('submit')
    expect(wrapper.vm.errors.password).toBe('Minimum 6 caractères.')
  })

  it('validates password confirmation match', async () => {
    const wrapper = mount(RegisterPage, { global: { plugins: [router] } })
    await flushPromises()
    await wrapper.find('#pseudo').setValue('test')
    await wrapper.find('#password').setValue('password123')
    await wrapper.find('#confirm').setValue('different')
    await wrapper.find('form').trigger('submit')
    expect(wrapper.vm.errors.confirm).toBe('Les mots de passe ne correspondent pas.')
  })

  it('calls API on submit and redirects', async () => {
    api.post.mockResolvedValue({
      data: { token: 'jwt', user: { id: 1, username: 'newuser' } },
    })
    const pushSpy = vi.spyOn(router, 'push')

    const wrapper = mount(RegisterPage, { global: { plugins: [router] } })
    await flushPromises()

    await wrapper.find('#pseudo').setValue('newuser')
    await wrapper.find('#password').setValue('password123')
    await wrapper.find('#confirm').setValue('password123')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(api.post).toHaveBeenCalledWith('/auth/register', {
      username: 'newuser',
      password: 'password123',
      skill_level: null,
      character_id: undefined,
    })
    const store = useAuthStore()
    expect(store.token).toBe('jwt')
    expect(pushSpy).toHaveBeenCalledWith('/')
  })

  it('maps level to API skill_level', () => {
    const wrapper = mount(RegisterPage, { global: { plugins: [router] } })
    wrapper.vm.level = 'Débutant'
    expect(wrapper.vm.apiSkillLevel).toBe('débutant')
    wrapper.vm.level = 'Professionnel'
    expect(wrapper.vm.apiSkillLevel).toBe('professionnel')
  })
})
