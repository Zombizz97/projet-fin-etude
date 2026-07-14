import { createRouter, createWebHistory } from 'vue-router'
import HomePage from "@/components/HomePage.vue";
import ForumPage from "@/components/ForumPage.vue";
import TopicPage from "@/components/TopicPage.vue";
import PlayersPage from "@/components/PlayersPage.vue";
import LoginPage from "@/components/LoginPage.vue";
import RegisterPage from "@/components/RegisterPage.vue";
import ProfilePage from '@/components/ProfilePage.vue'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: HomePage },
    { path: '/forum', name: 'forum', component: ForumPage },
    { path: '/forum/:id', name: 'topic', component: TopicPage },
    { path: '/players', name: 'players', component: PlayersPage },
    { path: '/login', name: 'login', component: LoginPage },
    { path: '/register', name: 'register', component: RegisterPage },
    { path: '/profile', name: 'profile', component: ProfilePage, meta: { requiresAuth: true } },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  // si pas authentifié mais token présent, tente de recharger
  if (!auth.isAuthenticated && localStorage.getItem('token')) {
    auth.setToken(localStorage.getItem('token'))
    await auth.fetchMe()
  }
  if (to.meta?.requiresAuth && !auth.isAuthenticated) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }
  return true
})

export default router
