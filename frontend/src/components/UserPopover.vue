<template>
    <div v-if="visible" class="popover-backdrop" @click.self="$emit('close')">
        <div class="popover" :style="position">
            <div v-if="loading" class="loading">Chargement…</div>
            <div v-else-if="error" class="error-msg">{{ error }}</div>
            <div v-else-if="userData" class="popover-body">
                <div class="popover-header">
                    <strong>{{ userData.username }}</strong>
                    <button class="close-btn" @click="$emit('close')">&times;</button>
                </div>
                <div class="info">
                    <span v-if="userData.skill_level" class="badge">{{ userData.skill_level }}</span>
                    <span v-else class="badge">Niveau non défini</span>
                </div>
                <div v-if="characters.length" class="characters">
                    <span v-for="c in characters" :key="c.name" class="character-chip">
                        {{ c.name }}
                        <img v-if="c.icon" :src="c.icon" alt="" class="icon" />
                    </span>
                </div>
                <div class="actions">
                    <template v-if="isMe">
                        <span class="me">C'est vous !</span>
                    </template>
                    <template v-else-if="!authStore.isAuthenticated">
                        <RouterLink to="/login" class="btn btn-sm">Connectez-vous</RouterLink>
                    </template>
                    <template v-else>
                        <button v-if="!friendshipStatus" class="btn btn-primary btn-sm" @click="sendRequest">+ Ajouter en ami</button>
                        <button v-else-if="friendshipStatus === 'pending'" class="btn btn-sm" disabled>Demande envoyée</button>
                        <button v-else-if="friendshipStatus === 'friend'" class="btn btn-sm" @click="removeFriend">✓ Amis · Retirer</button>
                        <button v-else-if="friendshipStatus === 'blocked'" class="btn btn-sm btn-danger" @click="unblock">Débloquer</button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

export default {
    name: 'UserPopover',
    props: {
        userId: { type: Number, required: true },
        visible: { type: Boolean, default: false },
        position: { type: Object, default: () => ({}) },
    },
    emits: ['close'],
    data() {
        return {
            userData: null,
            loading: false,
            error: null,
            authStore: useAuthStore(),
        }
    },
    computed: {
        isMe() {
            return this.authStore.user?.id === this.userId
        },
        friendshipStatus() {
            return this.userData?.friendship_status || null
        },
        characters() {
            if (!this.userData?.characters) return []
            return this.userData.characters.map(uc => ({
                name: uc.character?.name,
                icon: uc.character?.icon_path || null,
            })).filter(c => c.name)
        },
    },
    watch: {
        userId(v) {
            if (v) this.fetchUser()
        },
        visible(v) {
            if (v) this.fetchUser()
        },
    },
    methods: {
        async fetchUser() {
            this.loading = true
            this.error = null
            try {
                const res = await api.get(`/users/${this.userId}`)
                this.userData = res.data || null
            } catch (e) {
                this.error = 'Erreur chargement profil'
            } finally {
                this.loading = false
            }
        },
        async sendRequest() {
            try {
                await api.post(`/friends/${this.userId}`)
                this.userData.friendship_status = 'pending'
            } catch (e) {
                console.error(e)
            }
        },
        async removeFriend() {
            try {
                await api.delete(`/friends/${this.userId}`)
                this.userData.friendship_status = null
            } catch (e) {
                console.error(e)
            }
        },
        async unblock() {
            try {
                await api.post(`/friends/${this.userId}/unblock`)
                this.userData.friendship_status = null
            } catch (e) {
                console.error(e)
            }
        },
    },
}
</script>

<style scoped>
.popover-backdrop { position: fixed; inset: 0; z-index: 200; }
.popover { position: absolute; background: var(--color-bg-surface); border: 1px solid var(--color-bg-surface-alt); border-radius: .75rem; padding: 1rem; min-width: 220px; box-shadow: 0 8px 32px rgba(0,0,0,.3); }
.popover-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: .5rem; }
.popover-header strong { font-size: 1.05rem; }
.close-btn { background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--color-text-primary); padding: 0; line-height: 1; }
.info { margin-bottom: .5rem; }
.characters { display: flex; flex-wrap: wrap; gap: .35rem; margin-bottom: .75rem; }
.character-chip { display: inline-flex; align-items: center; gap: .25rem; padding: .15rem .5rem; border-radius: 1rem; background: var(--color-bg-surface-alt); font-size: .8rem; }
.icon { width: 16px; height: 16px; object-fit: contain; }
.actions { display: flex; gap: .5rem; }
.btn-sm { font-size: .8rem; padding: .25rem .65rem; }
.btn-danger { border-color: #f44336; color: #f44336; }
.btn-danger:hover { background: #f44336; color: #fff; }
.loading { color: #777; font-size: .85rem; }
.error-msg { color: #f44336; font-size: .85rem; }
.me { font-style: italic; color: var(--color-text-primary); font-size: .85rem; }
</style>
