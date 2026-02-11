import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token') || '')
  const user = ref(null)
  const isAuthenticated = computed(() => !!token.value)

  function setToken(newToken) {
    token.value = newToken || ''
    if (newToken) localStorage.setItem('token', newToken)
    else localStorage.removeItem('token')
    api.defaults.headers.common['Authorization'] = newToken ? `Bearer ${newToken}` : undefined
  }

  async function fetchMe() {
    if (!token.value) return null
    try {
      api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
      const { data } = await api.get('/auth/me')
      user.value = data.user || data
      return user.value
    } catch (e) {
      setToken('')
      user.value = null
      return null
    }
  }

  async function login({ username, password }) {
    const { data } = await api.post('/auth/login', { username, password })
    const receivedToken = data.token
    setToken(receivedToken)
    user.value = data.user
    return data
  }

  function logout() {
    setToken('')
    user.value = null
  }

  if (token.value) {
    api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
    fetchMe()
  }

  return { token, user, isAuthenticated, login, logout, fetchMe, setToken }
})

