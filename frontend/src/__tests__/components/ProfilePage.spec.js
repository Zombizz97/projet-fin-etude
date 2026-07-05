import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import ProfilePage from '@/components/ProfilePage.vue'
import { createRouter, createWebHistory } from 'vue-router'
import api from '@/services/api'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', name: 'login', component: { template: '<div>Login</div>' } },
    { path: '/profile', name: 'profile', component: { template: '<div>Profile</div>' } },
  ],
})

describe('ProfilePage', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
    router.push('/profile')
    await router.isReady()
  })

  it('redirects to login when not authenticated', async () => {
    const pushSpy = vi.spyOn(router, 'push')
    mount(ProfilePage, { global: { plugins: [router] } })
    expect(pushSpy).toHaveBeenCalledWith('/login')
  })

  it('pre-fills form from store', async () => {
    const store = useAuthStore()
    store.user = { id: 1, username: 'testuser', skill_level: 'intermédiaire' }
    store.setToken('jwt')

    const wrapper = mount(ProfilePage, { global: { plugins: [router] } })
    await flushPromises()

    expect(wrapper.find('#username').element.value).toBe('testuser')
    expect(wrapper.find('#skill_level').element.value).toBe('intermédiaire')
  })

  it('submits form and updates user', async () => {
    const store = useAuthStore()
    store.user = { id: 1, username: 'oldname', skill_level: null }
    store.setToken('jwt')

    api.put.mockResolvedValue({
      data: { user: { id: 1, username: 'newname', skill_level: 'confirmé' } },
    })

    const wrapper = mount(ProfilePage, { global: { plugins: [router] } })
    await flushPromises()

    await wrapper.find('#username').setValue('newname')
    await wrapper.find('#skill_level').setValue('confirmé')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(api.put).toHaveBeenCalledWith('/user', {
      username: 'newname',
      skill_level: 'confirmé',
      password: '',
    })
    expect(store.user.username).toBe('newname')
    expect(wrapper.vm.message).toBe('Profil mis à jour avec succès.')
  })

  it('shows error on failed update', async () => {
    const store = useAuthStore()
    store.user = { id: 1, username: 'test', skill_level: null }
    store.setToken('jwt')

    api.put.mockRejectedValue({ response: { data: { message: 'Erreur serveur' } } })

    const wrapper = mount(ProfilePage, { global: { plugins: [router] } })
    await flushPromises()

    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.vm.message).toBe('Erreur serveur')
  })
})
