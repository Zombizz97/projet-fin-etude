import { createRouter, createWebHistory } from 'vue-router'
import HomePage from "@/components/HomePage.vue";
import ForumPage from "@/components/ForumPage.vue";
import TopicPage from "@/components/TopicPage.vue";
import PlayersPage from "@/components/PlayersPage.vue";
import LoginPage from "@/components/LoginPage.vue";
import RegisterPage from "@/components/RegisterPage.vue";

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: HomePage },
    { path: '/forum', name: 'forum', component: ForumPage },
    { path: '/forum/:id', name: 'topic', component: TopicPage },
    { path: '/players', name: 'players', component: PlayersPage },
    { path: '/login', name: 'login', component: LoginPage },
    { path: '/register', name: 'register', component: RegisterPage }
  ],
})

export default router
