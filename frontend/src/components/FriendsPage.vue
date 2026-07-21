<template>
    <section class="friends">
        <header class="friends-header">
            <h1 class="title">Amis</h1>
            <div class="tabs">
                <button class="tab" :class="{ active: tab === 'friends' }" @click="tab = 'friends'">
                    Amis ({{ friends.length }})
                </button>
                <button class="tab" :class="{ active: tab === 'requests' }" @click="tab = 'requests'">
                    Demandes ({{ pending.length }})
                </button>
                <button class="tab" :class="{ active: tab === 'sent' }" @click="tab = 'sent'">
                    Envoyées ({{ sent.length }})
                </button>
                <button class="tab" :class="{ active: tab === 'add' }" @click="tab = 'add'">
                    Ajouter
                </button>
                <button class="tab" :class="{ active: tab === 'blocked' }" @click="tab = 'blocked'">
                    Bloqués ({{ blocked.length }})
                </button>
            </div>
        </header>

        <div v-if="tab === 'friends'" class="tab-content">
            <div v-if="loading" class="loading">Chargement…</div>
            <div v-else-if="friends.length === 0" class="empty">Vous n'avez pas encore d'amis.</div>
            <div v-else class="cards">
                <article v-for="f in friends" :key="f.id" class="card friend-card">
                    <div class="friend-info">
                        <span class="friend-name">{{ f.username }}</span>
                        <span v-if="f.skill_level" class="badge">{{ f.skill_level }}</span>
                    </div>
                    <div class="friend-actions">
                        <button class="btn" @click="removeFriend(f.id)">Supprimer</button>
                        <button class="btn btn-danger" @click="blockFriend(f.id)">Bloquer</button>
                    </div>
                </article>
            </div>
        </div>

        <div v-if="tab === 'requests'" class="tab-content">
            <div v-if="loadingPending" class="loading">Chargement…</div>
            <div v-else-if="pending.length === 0" class="empty">Aucune demande en attente.</div>
            <div v-else class="cards">
                <article v-for="p in pending" :key="p.id" class="card friend-card">
                    <div class="friend-info">
                        <span class="friend-name">{{ p.username }}</span>
                        <span v-if="p.skill_level" class="badge">{{ p.skill_level }}</span>
                    </div>
                    <div class="friend-actions">
                        <button class="btn btn-primary" @click="acceptRequest(p.id)">Accepter</button>
                        <button class="btn" @click="declineRequest(p.id)">Refuser</button>
                    </div>
                </article>
            </div>
        </div>

        <div v-if="tab === 'sent'" class="tab-content">
            <div v-if="loadingSent" class="loading">Chargement…</div>
            <div v-else-if="sent.length === 0" class="empty">Aucune demande envoyée.</div>
            <div v-else class="cards">
                <article v-for="s in sent" :key="s.id" class="card friend-card">
                    <div class="friend-info">
                        <span class="friend-name">{{ s.username }}</span>
                        <span v-if="s.skill_level" class="badge">{{ s.skill_level }}</span>
                        <span class="status">En attente</span>
                    </div>
                </article>
            </div>
        </div>

        <div v-if="tab === 'add'" class="tab-content">
            <form class="add-form" @submit.prevent="addFriend">
                <div class="search-row">
                    <input v-model="searchQuery" class="input" type="text"
                           placeholder="Nom d'utilisateur…" required />
                    <button type="submit" class="btn btn-primary" :disabled="sending">
                        {{ sending ? '…' : 'Ajouter' }}
                    </button>
                </div>
                <div v-if="addError" class="msg error">{{ addError }}</div>
                <div v-if="addSuccess" class="msg success">{{ addSuccess }}</div>
            </form>
        </div>

        <div v-if="tab === 'blocked'" class="tab-content">
            <div v-if="loadingBlocked" class="loading">Chargement…</div>
            <div v-else-if="blocked.length === 0" class="empty">Aucun utilisateur bloqué.</div>
            <div v-else class="cards">
                <article v-for="b in blocked" :key="b.id" class="card friend-card">
                    <div class="friend-info">
                        <span class="friend-name">{{ b.username }}</span>
                    </div>
                    <div class="friend-actions">
                        <button class="btn" @click="unblockUser(b.id)">Débloquer</button>
                    </div>
                </article>
            </div>
        </div>
    </section>
</template>

<script>
import api from '@/services/api'

export default {
    name: 'FriendsPage',
    data() {
        return {
            tab: 'friends',
            friends: [],
            pending: [],
            sent: [],
            blocked: [],
            loading: true,
            loadingPending: false,
            loadingSent: false,
            loadingBlocked: false,
            searchQuery: '',
            sending: false,
            addError: null,
            addSuccess: null,
        }
    },
    mounted() {
        this.fetchFriends()
        this.fetchPending()
        this.fetchSent()
    },
    methods: {
        async fetchFriends() {
            this.loading = true
            try {
                const res = await api.get('/friends')
                this.friends = Array.isArray(res.data) ? res.data : []
            } catch (e) {
                console.error('Erreur chargement amis', e)
            } finally {
                this.loading = false
            }
        },
        async fetchPending() {
            this.loadingPending = true
            try {
                const res = await api.get('/friends/pending')
                this.pending = Array.isArray(res.data) ? res.data : []
            } catch (e) {
                console.error('Erreur chargement demandes', e)
            } finally {
                this.loadingPending = false
            }
        },
        async fetchSent() {
            this.loadingSent = true
            try {
                const res = await api.get('/friends/sent')
                this.sent = Array.isArray(res.data) ? res.data : []
            } catch (e) {
                console.error('Erreur chargement envois', e)
            } finally {
                this.loadingSent = false
            }
        },
        async fetchBlocked() {
            this.loadingBlocked = true
            try {
                const res = await api.get('/friends/blocked')
                this.blocked = Array.isArray(res.data) ? res.data : []
            } catch (e) {
                console.error('Erreur chargement bloqués', e)
            } finally {
                this.loadingBlocked = false
            }
        },
        async addFriend() {
            if (!this.searchQuery.trim()) return
            this.sending = true
            this.addError = null
            this.addSuccess = null
            try {
                const res = await api.get('/players')
                const players = Array.isArray(res.data) ? res.data : []
                const match = players.find(p =>
                    p.username && p.username.toLowerCase() === this.searchQuery.trim().toLowerCase()
                )
                if (!match) {
                    this.addError = 'Aucun utilisateur trouvé avec ce nom.'
                    return
                }
                await api.post(`/friends/${match.id}`)
                this.addSuccess = `Demande d'ami envoyée à ${match.username} !`
                this.searchQuery = ''
                this.fetchSent()
            } catch (e) {
                this.addError = e.response?.data?.message || 'Erreur lors de l\'envoi.'
            } finally {
                this.sending = false
            }
        },
        async acceptRequest(id) {
            try {
                await api.post(`/friends/${id}/accept`)
                this.pending = this.pending.filter(p => p.id !== id)
                this.fetchFriends()
            } catch (e) {
                console.error('Erreur acceptation', e)
            }
        },
        async declineRequest(id) {
            try {
                await api.delete(`/friends/${id}/accept`)
                this.pending = this.pending.filter(p => p.id !== id)
            } catch (e) {
                console.error('Erreur refus', e)
            }
        },
        async removeFriend(id) {
            try {
                await api.delete(`/friends/${id}`)
                this.friends = this.friends.filter(f => f.id !== id)
            } catch (e) {
                console.error('Erreur suppression ami', e)
            }
        },
        async blockFriend(id) {
            try {
                await api.post(`/friends/${id}/block`)
                this.friends = this.friends.filter(f => f.id !== id)
            } catch (e) {
                console.error('Erreur blocage', e)
            }
        },
        async unblockUser(id) {
            try {
                await api.post(`/friends/${id}/unblock`)
                this.blocked = this.blocked.filter(b => b.id !== id)
            } catch (e) {
                console.error('Erreur déblocage', e)
            }
        },
    },
    watch: {
        tab(v) {
            if (v === 'blocked' && this.blocked.length === 0) this.fetchBlocked()
        }
    }
}
</script>

<style scoped>
.tabs { display: flex; gap: .5rem; margin-top: 1rem; flex-wrap: wrap; }
.tab { padding: .5rem 1rem; border: 1px solid var(--color-bg-surface); border-radius: .5rem; background: transparent; color: var(--color-text-primary); cursor: pointer; }
.tab.active { border-color: var(--color-accent-primary); color: var(--color-accent-primary); font-weight: 600; }
.tab-content { margin-top: 1rem; }
.friend-card { display: flex; justify-content: space-between; align-items: center; padding: .75rem 1rem; }
.friend-info { display: flex; align-items: center; gap: .75rem; }
.friend-name { font-weight: 600; }
.friend-actions { display: flex; gap: .5rem; }
.btn-danger { border-color: #f44336; color: #f44336; }
.btn-danger:hover { background: #f44336; color: #fff; }
.add-form { display: flex; flex-direction: column; gap: .5rem; max-width: 400px; }
.search-row { display: flex; gap: .5rem; }
.search-row .input { flex: 1; }
.msg { font-size: .85rem; }
.msg.error { color: #f44336; }
.msg.success { color: #4caf50; }
.loading { color: #777; }
.empty { color: #777; font-style: italic; }
.status { font-size: .8rem; color: var(--color-text-primary); font-style: italic; }
</style>
