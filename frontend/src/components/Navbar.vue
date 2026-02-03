<script setup>
const props = defineProps({
    isAuthenticated: { type: Boolean, default: false },
    user: { type: Object, default: null }
})
</script>

<template>
    <nav class="navbar bg-surface">
        <div class="navbar-left">
            <RouterLink to="/" class="brand">
                <svg class="brand-logo" viewBox="0 0 64 64" aria-hidden="true">
                    <circle cx="32" cy="32" r="28" fill="#8b5cf6" />
                    <text x="32" y="40" text-anchor="middle" font-size="28" fill="#fff">S</text>
                </svg>
                <span class="brand-name">SmashConnect</span>
            </RouterLink>
        </div>

        <div class="navbar-center">
            <RouterLink to="/" class="navlink" active-class="active">Acceuil</RouterLink>
            <RouterLink to="/forum" class="navlink" active-class="active">Forum</RouterLink>
            <RouterLink to="/joueurs" class="navlink" active-class="active">Joueurs</RouterLink>
        </div>

        <div class="navbar-right">
            <template v-if="props.isAuthenticated && props.user">
                <img :src="props.user.avatarUrl" alt="Avatar" class="avatar" />
                <span class="pseudo">{{ props.user.pseudo }}</span>
            </template>
            <template v-else>
                <RouterLink to="/login" class="btn">Se connecter</RouterLink>
                <RouterLink to="/register" class="btn btn-primary">Créer un compte</RouterLink>
            </template>
        </div>
    </nav>
</template>

<style scoped>

.navbar {
    position: sticky;
    top: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 28px;
    height: 70px;
}

.navbar-left, .navbar-center, .navbar-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.navbar-center {
    flex: 1;
    justify-content: center;
    gap: 15px;
}

.brand {
    display: inline-flex;
    align-items: center;
    gap: 20px;
    color: inherit;
    text-decoration: none;
}

.brand-logo {
    width: 72px;
    height: 72px;
}

.brand-name {
    font-weight: 900;
    letter-spacing: 0.3px;
    font-size: 2rem;
    line-height: 1.4;
}

.navlink {
    color: var(--color-text-primary);
    text-decoration: none;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 1.2rem;
}

.navlink:hover {
    background: var(--color-bg-surface-alt);
    color: var(--color-white);
}

.active {
    background: var(--color-bg-main);
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
</style>