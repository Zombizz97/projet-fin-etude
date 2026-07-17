import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import FriendsPage from '@/components/FriendsPage.vue'
import { createRouter, createWebHistory } from 'vue-router'
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
  routes: [{ path: '/friends', name: 'friends', component: FriendsPage }],
})

const mockFriends = [
  { id: 1, username: 'FriendOne', skill_level: 'débutant' },
  { id: 2, username: 'FriendTwo', skill_level: 'confirmé' },
]

const mockPending = [{ id: 3, username: 'Requester', skill_level: 'professionnel' }]
const mockSent = [{ id: 4, username: 'TargetUser', skill_level: 'intermédiaire' }]
const mockBlocked = [{ id: 5, username: 'BlockedUser' }]
const mockPlayers = [
  { id: 1, username: 'FriendOne' },
  { id: 10, username: 'NewPlayer' },
  { id: 11, username: 'AnotherPlayer' },
]

describe('FriendsPage', () => {
  beforeEach(async () => {
    localStorage.clear()
    api.get.mockReset()
    api.post.mockReset()
    api.delete.mockReset()
    api.get
      .mockResolvedValueOnce({ data: mockFriends })
      .mockResolvedValueOnce({ data: mockPending })
      .mockResolvedValueOnce({ data: mockSent })
    router.push('/friends')
    await router.isReady()
  })

  const mountPage = () => mount(FriendsPage, { global: { plugins: [router] } })

  it('fetches friends, pending and sent on mount', async () => {
    mountPage()
    await flushPromises()
    expect(api.get).toHaveBeenCalledWith('/friends')
    expect(api.get).toHaveBeenCalledWith('/friends/pending')
    expect(api.get).toHaveBeenCalledWith('/friends/sent')
  })

  it('renders friends tab by default', async () => {
    const wrapper = mountPage()
    await flushPromises()
    expect(wrapper.text()).toContain('FriendOne')
    expect(wrapper.text()).toContain('FriendTwo')
    expect(wrapper.text()).toContain('Supprimer')
    expect(wrapper.text()).toContain('Bloquer')
  })

  it('renders requests tab', async () => {
    const wrapper = mountPage()
    await flushPromises()
    await wrapper.findAll('.tab').at(1).trigger('click')
    expect(wrapper.text()).toContain('Requester')
    expect(wrapper.text()).toContain('Accepter')
    expect(wrapper.text()).toContain('Refuser')
  })

  it('renders sent tab', async () => {
    const wrapper = mountPage()
    await flushPromises()
    await wrapper.findAll('.tab').at(2).trigger('click')
    expect(wrapper.text()).toContain('TargetUser')
    expect(wrapper.text()).toContain('En attente')
  })

  it('renders add tab', async () => {
    const wrapper = mountPage()
    await flushPromises()
    await wrapper.findAll('.tab').at(3).trigger('click')
    expect(wrapper.find('input').exists()).toBe(true)
    expect(wrapper.find('form').exists()).toBe(true)
  })

  it('renders blocked tab and fetches blocked on click', async () => {
    api.get.mockResolvedValueOnce({ data: mockBlocked })
    const wrapper = mountPage()
    await flushPromises()
    await wrapper.findAll('.tab').at(4).trigger('click')
    await flushPromises()
    expect(api.get).toHaveBeenCalledWith('/friends/blocked')
    expect(wrapper.text()).toContain('BlockedUser')
    expect(wrapper.text()).toContain('Débloquer')
  })

  it('sends friend request from add tab', async () => {
    api.get.mockResolvedValueOnce({ data: mockPlayers })
    api.get.mockResolvedValueOnce({ data: [] })
    api.get.mockResolvedValueOnce({ data: [] })
    api.post.mockResolvedValueOnce({ data: { message: 'Demande envoyée' } })

    const wrapper = mountPage()
    await flushPromises()
    await wrapper.findAll('.tab').at(3).trigger('click')
    wrapper.find('input').setValue('NewPlayer')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(api.get).toHaveBeenCalledWith('/players')
    expect(api.post).toHaveBeenCalledWith('/friends/10')
    expect(wrapper.text()).toContain('Demande')
  })

  it('shows error when user not found in add tab', async () => {
    api.get.mockResolvedValueOnce({ data: mockPlayers })
    api.get.mockResolvedValueOnce({ data: [] })
    api.get.mockResolvedValueOnce({ data: [] })

    const wrapper = mountPage()
    await flushPromises()
    await wrapper.findAll('.tab').at(3).trigger('click')
    wrapper.find('input').setValue('Nonexistent')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.text()).toContain('Aucun utilisateur trouvé')
  })

  it('accepts a friend request', async () => {
    api.post.mockResolvedValueOnce({})
    api.get.mockResolvedValueOnce({ data: mockFriends })

    const wrapper = mountPage()
    await flushPromises()

    await wrapper.findAll('.tab').at(1).trigger('click')
    await flushPromises()

    await wrapper.find('.btn-primary').trigger('click')
    await flushPromises()

    expect(api.post).toHaveBeenCalledWith('/friends/3/accept')
  })

  it('declines a friend request', async () => {
    api.delete.mockResolvedValueOnce({})

    const wrapper = mountPage()
    await flushPromises()

    wrapper.vm.tab = 'requests'
    await wrapper.vm.$nextTick()

    const buttons = wrapper.findAll('.friend-card .btn')
    await buttons.at(1).trigger('click')
    await flushPromises()

    expect(api.delete).toHaveBeenCalledWith('/friends/3/accept')
  })

  it('removes a friend', async () => {
    api.delete.mockResolvedValueOnce({})

    const wrapper = mountPage()
    await flushPromises()

    const buttons = wrapper.findAll('.friend-card .btn')
    await buttons.at(0).trigger('click')
    await flushPromises()

    expect(api.delete).toHaveBeenCalledWith('/friends/1')
  })

  it('blocks a friend', async () => {
    api.post.mockResolvedValueOnce({})

    const wrapper = mountPage()
    await flushPromises()

    const buttons = wrapper.findAll('.friend-card .btn-danger')
    await buttons.at(0).trigger('click')
    await flushPromises()

    expect(api.post).toHaveBeenCalledWith('/friends/1/block')
  })

  it('unblocks a user', async () => {
    api.get.mockResolvedValueOnce({ data: mockBlocked })
    api.post.mockResolvedValueOnce({})

    const wrapper = mountPage()
    await flushPromises()

    wrapper.vm.tab = 'blocked'
    await wrapper.vm.$nextTick()
    await flushPromises()

    const buttons = wrapper.findAll('.friend-card .btn')
    await buttons.at(0).trigger('click')
    await flushPromises()

    expect(api.post).toHaveBeenCalledWith('/friends/5/unblock')
  })

  it('shows empty state when no friends', async () => {
    api.get
      .mockReset()
      .mockResolvedValueOnce({ data: [] })
      .mockResolvedValueOnce({ data: [] })
      .mockResolvedValueOnce({ data: [] })

    const wrapper = mountPage()
    await flushPromises()
    expect(wrapper.text()).toContain("Vous n'avez pas encore d'amis")
  })

  it('shows empty state when no pending requests', async () => {
    api.get
      .mockReset()
      .mockResolvedValueOnce({ data: [] })
      .mockResolvedValueOnce({ data: [] })
      .mockResolvedValueOnce({ data: [] })

    const wrapper = mountPage()
    await flushPromises()
    await wrapper.findAll('.tab').at(1).trigger('click')
    expect(wrapper.text()).toContain('Aucune demande en attente')
  })
})
