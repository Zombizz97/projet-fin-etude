<template>
    <nav class="navbar bg-surface top-0 left-0 right-0">
        <div class="navbar-left">
            <RouterLink to="/" class="brand">
                <img :src="brandLogo" alt="Logo" class="brand-logo" />
            </RouterLink>
        </div>

        <div class="navbar-center">
            <RouterLink to="/" class="navlink" active-class="active">Acceuil</RouterLink>
            <RouterLink to="/forum" class="navlink" active-class="active">Forum</RouterLink>
            <RouterLink to="/players" class="navlink" active-class="active">Joueurs</RouterLink>
        </div>

        <div class="navbar-right">
            <template v-if="isAuthenticated && user">
                <img :src="user.avatarUrl || '/favicon.svg'" alt="Avatar" class="avatar" />
                <RouterLink to="/profile" class="pseudo">{{ user.username }}</RouterLink>
                <button class="btn" @click="onLogout">Se déconnecter</button>
            </template>
            <template v-else>
                <RouterLink to="/login" class="btn">Se connecter</RouterLink>
                <RouterLink to="/register" class="btn btn-primary">Créer un compte</RouterLink>
            </template>
        </div>

        <button
            class="burger"
            @click="toggle"
            :aria-expanded="isOpen ? 'true' : 'false'"
            aria-controls="mobile-menu"
            aria-label="Ouvrir le menu"
        >
            <span class="sr-only">Menu</span>
            <svg width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="currentColor" d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z"/>
            </svg>
        </button>
    </nav>

    <div v-show="isOpen" class="mobile-menu-backdrop" id="mobile-menu" @click.self="close">
        <aside class="mobile-menu-panel">
            <div class="mobile-menu-header">
                <span class="mobile-title">Menu</span>
                <button class="close" @click="close" aria-label="Fermer le menu">
                    <svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="currentColor" d="M18.3 5.71 12 12l6.3 6.29-1.41 1.42L10.59 13.4 4.3 19.71 2.89 18.3 9.17 12 2.89 5.71 4.3 4.29l6.29 6.3 6.29-6.3 1.42 1.42z"/>
                    </svg>
                </button>
            </div>

            <nav class="mobile-links">
                <RouterLink to="/" class="mobile-link" active-class="active" @click="close">Acceuil</RouterLink>
                <RouterLink to="/forum" class="mobile-link" active-class="active" @click="close">Forum</RouterLink>
                <RouterLink to="/players" class="mobile-link" active-class="active" @click="close">Joueurs</RouterLink>
            </nav>

            <div class="mobile-actions">
                <template v-if="isAuthenticated && user">
                    <div class="user-row">
                        <img :src="user.avatarUrl || '/favicon.svg'" alt="Avatar" class="avatar" />
                        <span class="pseudo">{{ user.username }}</span>
                    </div>
                    <RouterLink to="/profile" class="btn w-full" @click="close">Mon profil</RouterLink>
                    <button class="btn w-full" @click="onLogoutAndClose">Se déconnecter</button>
                </template>
                <template v-else>
                    <RouterLink to="/login" class="btn w-full" @click="close">Se connecter</RouterLink>
                    <RouterLink to="/register" class="btn btn-primary w-full" @click="close">Créer un compte</RouterLink>
                </template>
            </div>
        </aside>
    </div>
</template>

<script>
import { useAuthStore } from '../stores/auth'

export default {
    name: 'Navbar',
    computed: {
        isAuthenticated() {
            const auth = useAuthStore()
            return auth.isAuthenticated
        },
        user() {
            const auth = useAuthStore()
            return auth.user
        }
    },
    data() {
        return { isOpen: false, brandLogo: '/full-logo.svg' }
    },
    methods: {
        open() { this.isOpen = true },
        close() { this.isOpen = false },
        toggle() { this.isOpen ? this.close() : this.open() },
        onKeydown(e) { if (e.key === 'Escape') this.close() },
        onLogout() {
            const auth = useAuthStore()
            auth.logout()
            this.$router.push('/')
        },
        onLogoutAndClose() {
            this.onLogout()
            this.close()
        }
    },
    async mounted() {
        window.addEventListener('keydown', this.onKeydown)
        const auth = useAuthStore()
        await auth.fetchMe()
    },
    beforeUnmount() {
        window.removeEventListener('keydown', this.onKeydown)
    }
}
</script>


<style scoped>
/* Barre */
.navbar {
    position: sticky;
    top: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 200px;
    gap: 16px;
}

.navbar-left,
.navbar-center,
.navbar-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.navbar-center {
    flex: 1 1 auto;
    justify-content: center;
    gap: 14px;
}

.brand {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    color: inherit;
    text-decoration: none;
    font-size: 1.25rem;
    line-height: 1;
}

.brand-logo {
    height: 3em;
    width: auto;
    display: inline-block;
    vertical-align: middle;
}

.navlink {
    color: var(--color-text-primary);
    text-decoration: none;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 1.05rem;
}

.navlink:hover {
    background: var(--color-bg-surface-alt);
    color: var(--color-white);
}

.avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid var(--color-bg-surface-alt);
}

.pseudo {
    font-weight: 600;
    font-size: 1rem;
}

.burger {
    display: none;
    align-items: center;
    justify-content: center;
    padding: 8px;
    border-radius: 8px;
    background: transparent;
    border: 1px solid transparent;
    color: var(--color-text-primary);
}
.burger:hover {
    background: var(--color-bg-surface-alt);
    color: var(--color-white);
}

/* Panneau mobile */
.mobile-menu-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 60;
}

.mobile-menu-panel {
    position: absolute;
    top: 0;
    right: 0;
    width: min(84vw, 360px);
    height: 100%;
    background: var(--color-bg-surface);
    display: flex;
    flex-direction: column;
    padding: 16px;
    gap: 12px;
    box-shadow: -8px 0 24px rgba(0,0,0,0.25);
}

.mobile-menu-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--color-bg-surface-alt);
}
.mobile-title {
    font-weight: 700;
    font-size: 1.1rem;
}
.close {
    background: transparent;
    border: 1px solid transparent;
    border-radius: 8px;
    padding: 6px;
    color: var(--color-text-primary);
}
.close:hover {
    background: var(--color-bg-surface-alt);
    color: var(--color-white);
}

.mobile-links {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding-top: 8px;
}
.mobile-link {
    display: block;
    text-decoration: none;
    color: var(--color-text-primary);
    padding: 10px 12px;
    border-radius: 8px;
}
.mobile-link:hover {
    background: var(--color-bg-surface-alt);
    color: var(--color-white);
}

.mobile-actions {
    margin-top: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding-top: 12px;
    border-top: 1px solid var(--color-bg-surface-alt);
}
.user-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.sr-only {
    position: absolute;
    width: 1px; height: 1px;
    padding: 0; margin: -1px;
    overflow: hidden; clip: rect(0,0,0,0);
    border: 0;
}

@media (max-width: 1024px) {
    .navbar { padding: 12px 16px; gap: 12px; }
    .brand-logo {
        height: 3em;
        width: auto; }
    .navlink { font-size: 1rem; padding: 8px 12px; }
}

@media (max-width: 768px) {
    .navbar-center, .navbar-right { display: none; }
    .burger { display: inline-flex; margin-left: auto; }
    .navbar { padding: 12px 14px; }
}
</style>
