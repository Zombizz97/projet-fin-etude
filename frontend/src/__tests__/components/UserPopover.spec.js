import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import UserPopover from '@/components/UserPopover.vue'
import { createRouter, createWebHistory } from 'vue-router'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

vi.mock('@/services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    delete: vi.fn(),
    defaults: { headers: { common: {} } },
  },
}))

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', name: 'login', component: { template: '<div>Login</div>' } },
  ],
})

const mockUser = {
  id: 2,
  username: 'TestUser',
  skill_level: 'confirmé',
  characters: [
    { character: { name: 'Mario', icon_path: '/mario.png' } },
    { character: { name: 'Luigi', icon_path: null } },
  ],
}

function mountPopover(props = {}, pinia) {
  const p = pinia || createPinia()
  return mount(UserPopover, {
    props: { userId: 2, visible: false, ...props },
    global: { plugins: [router, p] },
  })
}

function createAuthPinia(overrides = {}) {
  const pinia = createPinia()
  setActivePinia(pinia)
  const auth = useAuthStore()
  auth.setToken(overrides.token || 'valid-token')
  auth.user = overrides.user || { id: 1, username: 'Me' }
  return pinia
}

function mountAuthPopover(props = {}, overrides = {}) {
  const pinia = createAuthPinia(overrides)
  return mount(UserPopover, {
    props: { userId: 2, visible: false, ...props },
    global: { plugins: [router, pinia] },
  })
}

describe('UserPopover', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
  })

  it('fetches user data when visible becomes true', async () => {
    const wrapper = mountPopover({ visible: false })
    api.get.mockResolvedValueOnce({ data: mockUser })
    await wrapper.setProps({ visible: true })
    await flushPromises()
    expect(api.get).toHaveBeenCalledWith('/users/2')
  })

  it('does not fetch when visible is false', () => {
    mountPopover({ visible: false })
    expect(api.get).not.toHaveBeenCalled()
  })

  it('shows user info when loaded', async () => {
    const wrapper = mountPopover({ visible: false })
    api.get.mockResolvedValueOnce({ data: mockUser })
    await wrapper.setProps({ visible: true })
    await flushPromises()
    expect(wrapper.text()).toContain('TestUser')
    expect(wrapper.text()).toContain('confirmé')
    expect(wrapper.text()).toContain('Mario')
    expect(wrapper.text()).toContain('Luigi')
  })

  it('shows "C\'est vous !" when viewing own profile', async () => {
    const wrapper = mountAuthPopover({ visible: false }, { user: { id: 2, username: 'TestUser' } })
    api.get.mockResolvedValueOnce({ data: { ...mockUser, id: 2 } })
    await wrapper.setProps({ visible: true })
    await flushPromises()
    expect(wrapper.text()).toContain("C'est vous")
  })

  it('shows login link when not authenticated', async () => {
    const wrapper = mountPopover({ visible: false })
    api.get.mockResolvedValueOnce({ data: mockUser })
    await wrapper.setProps({ visible: true })
    await flushPromises()
    expect(wrapper.find('a').exists()).toBe(true)
    expect(wrapper.text()).toContain('Connectez-vous')
  })

  it('shows add friend button when no friendship', async () => {
    const wrapper = mountAuthPopover({ visible: false })
    api.get.mockResolvedValueOnce({ data: { ...mockUser, friendship_status: null } })
    await wrapper.setProps({ visible: true })
    await flushPromises()
    expect(wrapper.text()).toContain('Ajouter en ami')
  })

  it('shows pending status when request sent', async () => {
    const wrapper = mountAuthPopover({ visible: false })
    api.get.mockResolvedValueOnce({ data: { ...mockUser, friendship_status: 'pending' } })
    await wrapper.setProps({ visible: true })
    await flushPromises()
    expect(wrapper.text()).toContain('Demande envoyée')
    expect(wrapper.find('button:disabled').exists()).toBe(true)
  })

  it('shows friend status with remove option', async () => {
    const wrapper = mountAuthPopover({ visible: false })
    api.get.mockResolvedValueOnce({ data: { ...mockUser, friendship_status: 'friend' } })
    await wrapper.setProps({ visible: true })
    await flushPromises()
    expect(wrapper.text()).toContain('Amis')
    expect(wrapper.text()).toContain('Retirer')
  })

  it('shows unblock button when blocked', async () => {
    const wrapper = mountAuthPopover({ visible: false })
    api.get.mockResolvedValueOnce({ data: { ...mockUser, friendship_status: 'blocked' } })
    await wrapper.setProps({ visible: true })
    await flushPromises()
    expect(wrapper.text()).toContain('Débloquer')
  })

  it('sends friend request on button click', async () => {
    const wrapper = mountAuthPopover({ visible: false })
    api.get.mockResolvedValueOnce({ data: { ...mockUser, friendship_status: null } })
    api.post.mockResolvedValueOnce({})
    await wrapper.setProps({ visible: true })
    await flushPromises()

    await wrapper.find('.btn-primary').trigger('click')
    await flushPromises()
    expect(api.post).toHaveBeenCalledWith('/friends/2')
  })

  it('removes friend on button click', async () => {
    const wrapper = mountAuthPopover({ visible: false })
    api.get.mockResolvedValueOnce({ data: { ...mockUser, friendship_status: 'friend' } })
    api.delete.mockResolvedValueOnce({})
    await wrapper.setProps({ visible: true })
    await flushPromises()

    await wrapper.find('.actions .btn-sm').trigger('click')
    await flushPromises()
    expect(api.delete).toHaveBeenCalledWith('/friends/2')
  })

  it('unblocks user on button click', async () => {
    const wrapper = mountAuthPopover({ visible: false })
    api.get.mockResolvedValueOnce({ data: { ...mockUser, friendship_status: 'blocked' } })
    api.post.mockResolvedValueOnce({})
    await wrapper.setProps({ visible: true })
    await flushPromises()

    await wrapper.find('.actions .btn-sm').trigger('click')
    await flushPromises()
    expect(api.post).toHaveBeenCalledWith('/friends/2/unblock')
  })

  it('emits close on backdrop click', async () => {
    const wrapper = mountPopover({ visible: true })
    await flushPromises()
    await wrapper.find('.popover-backdrop').trigger('click.self')
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('shows loading state when fetching', async () => {
    const wrapper = mountPopover({ visible: false })
    api.get.mockResolvedValueOnce(new Promise(() => {}))
    await wrapper.setProps({ visible: true })
    expect(wrapper.text()).toContain('Chargement')
  })

  it('shows error on fetch failure', async () => {
    const wrapper = mountPopover({ visible: false })
    api.get.mockRejectedValueOnce(new Error('Network'))
    await wrapper.setProps({ visible: true })
    await flushPromises()
    expect(wrapper.text()).toContain('Erreur chargement profil')
  })
})
