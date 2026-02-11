<template>
    <section class="hero"
             :style="{ backgroundImage: 'url(/img/ultimate.jpg)' }">
        <div class="hero-content">
            <h1 class="hero-title">Bienvenue sur SmashConnect</h1>
            <p class="hero-subtitle">
                Rejoignez la communauté, discutez sur le forum et trouvez des joueurs près de chez vous.
            </p>
            <div class="hero-actions">
                <RouterLink v-if="isAuthenticated" to="/profile" class="btn btn-primary">Profil</RouterLink>
                <RouterLink v-else to="/register" class="btn btn-primary">Créer un compte</RouterLink>
                <RouterLink to="/forum" class="btn">Accéder au forum</RouterLink>
            </div>
        </div>
    </section>
</template>

<script>
import { useAuthStore } from '@/stores/auth'

export default {
    name: 'HomePage',
    computed: {
        isAuthenticated() {
            const auth = useAuthStore()
            return auth.isAuthenticated
        }
    }
}
</script>

<style scoped>
.hero {
    text-align: center;
    align-items: center;
    justify-content: center;
    display: flex;
}
</style>