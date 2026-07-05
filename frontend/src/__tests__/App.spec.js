import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import App from '../App.vue'

vi.mock('@/services/api', () => ({
  default: {
    get: vi.fn(),
    defaults: { headers: { common: {} } },
  },
}))

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: { template: '<div>Home Page Content</div>' } },
  ],
})

describe('App', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
    router.push('/')
    await router.isReady()
  })

  it('renders navbar and router view', () => {
    const wrapper = mount(App, {
      global: { plugins: [router] },
    })
    expect(wrapper.find('nav.navbar').exists()).toBe(true)
    expect(wrapper.text()).toContain('Home Page Content')
  })
})
