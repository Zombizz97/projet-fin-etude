import { vi } from 'vitest'
import { config as testUtilsConfig } from '@vue/test-utils'
import PrimeVue from 'primevue/config'

vi.mock('@/services/api', () => {
  const mockAxios = {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    defaults: { headers: { common: {} } },
  }
  return { default: mockAxios }
})

const localStorageMock = (() => {
  let store = {}
  return {
    getItem: vi.fn((key) => store[key] ?? null),
    setItem: vi.fn((key, value) => { store[key] = String(value) }),
    removeItem: vi.fn((key) => { delete store[key] }),
    clear: vi.fn(() => { store = {} }),
  }
})()

Object.defineProperty(window, 'localStorage', { value: localStorageMock })

Object.defineProperty(window, 'matchMedia', {
  value: vi.fn().mockImplementation((query) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
})

testUtilsConfig.global.plugins = testUtilsConfig.global.plugins || []
testUtilsConfig.global.plugins.push(PrimeVue)
