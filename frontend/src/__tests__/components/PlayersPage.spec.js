import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import PlayersPage from '@/components/PlayersPage.vue'
import { createRouter, createWebHistory } from 'vue-router'
import api from '@/services/api'

vi.mock('@/services/api', () => ({
  default: {
    get: vi.fn(),
    defaults: { headers: { common: {} } },
  },
}))

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/players', name: 'players', component: PlayersPage },
  ],
})

const mockPlayers = [
  { id: 1, username: 'PlayerOne', skill_level: 'débutant', characters: [{ character: { name: 'Mario', icon_path: '/mario.png' }, is_main: true }] },
  { id: 2, username: 'PlayerTwo', skill_level: 'confirmé', characters: [{ character: { name: 'Luigi', icon_path: null }, is_main: false }] },
  { id: 3, username: 'ZeldaFan', skill_level: 'professionnel', characters: [] },
]

const mockCharacters = [
  { id: 1, name: 'Mario', icon_path: '/mario.png' },
  { id: 2, name: 'Luigi', icon_path: null },
]

describe('PlayersPage', () => {
  beforeEach(async () => {
    vi.clearAllMocks()
    api.get.mockResolvedValueOnce({ data: mockPlayers })
    api.get.mockResolvedValueOnce({ data: mockCharacters })
    router.push('/players')
    await router.isReady()
  })

  it('fetches players and characters on mount', async () => {
    mount(PlayersPage, { global: { plugins: [router] } })
    await flushPromises()
    expect(api.get).toHaveBeenCalledWith('http://localhost:8000/api/players')
    expect(api.get).toHaveBeenCalledWith('http://localhost:8000/api/characters')
  })

  it('renders player cards', async () => {
    const wrapper = mount(PlayersPage, { global: { plugins: [router] } })
    await flushPromises()
    const cards = wrapper.findAll('.card')
    expect(cards).toHaveLength(3)
    expect(cards.at(0).text()).toContain('PlayerOne')
  })

  it('normalizes player characters', async () => {
    const wrapper = mount(PlayersPage, { global: { plugins: [router] } })
    await flushPromises()
    const normalized = wrapper.vm.normalizedPlayers
    expect(normalized[0].characterInfos).toEqual([{ name: 'Mario', icon: '/mario.png' }])
    expect(normalized[2].characterInfos).toEqual([])
  })

  it('filters by search text', async () => {
    const wrapper = mount(PlayersPage, { global: { plugins: [router] } })
    await flushPromises()
    wrapper.vm.search = 'player'
    expect(wrapper.vm.filteredPlayers).toHaveLength(2)
  })

  it('filters by level', async () => {
    const wrapper = mount(PlayersPage, { global: { plugins: [router] } })
    await flushPromises()
    wrapper.vm.filterLevel = 'confirmé'
    expect(wrapper.vm.filteredPlayers).toHaveLength(1)
    expect(wrapper.vm.filteredPlayers[0].username).toBe('PlayerTwo')
  })

  it('resets page when search changes', async () => {
    const wrapper = mount(PlayersPage, { global: { plugins: [router] } })
    await flushPromises()
    wrapper.vm.currentPage = 2
    wrapper.vm.search = 'test'
    expect(wrapper.vm.currentPage).toBe(1)
  })

  it('paginates results', async () => {
    const wrapper = mount(PlayersPage, { global: { plugins: [router] } })
    await flushPromises()
    wrapper.vm.pageSize = 2
    expect(wrapper.vm.paginatedPlayers).toHaveLength(2)
    expect(wrapper.vm.totalPages).toBe(2)
  })

  it('shows empty state when no players match', async () => {
    const wrapper = mount(PlayersPage, { global: { plugins: [router] } })
    await flushPromises()
    wrapper.vm.search = 'NonexistentPlayer'
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.filteredPlayers).toHaveLength(0)
    expect(wrapper.text()).toContain('Aucun joueur trouvé.')
  })
})
