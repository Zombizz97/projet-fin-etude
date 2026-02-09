<!-- language: javascript -->
<template>
    <section class="forum">
        <header class="forum-header">
            <h1 class="title">Forum</h1>
            <div class="actions">
                <div class="search">
                    <input
                        class="input"
                        type="text"
                        placeholder="Rechercher un topic..."
                        v-model="q"
                        @input="onSearchInput"
                    />
                </div>
                <div class="filters">
                    <select v-model="stateFilter" class="select" @change="setPage(1)">
                        <option value="all">Tous les états</option>
                        <option value="active">Actifs</option>
                        <option value="archived">Archivés</option>
                    </select>

                    <select v-model="sortBy" class="select" @change="setPage(1)">
                        <option value="createdAt">Date de publication</option>
                        <option value="lastMessageAt">Dernier message</option>
                        <option value="messagesCount">Nombre de messages</option>
                    </select>

                    <button class="btn" @click="toggleSortDir">
                        Tri {{ sortDir === 'asc' ? '↑' : '↓' }}
                    </button>
                </div>
            </div>
        </header>

        <div class="cards">
            <article v-for="t in paged" :key="t.id" class="card">
                <div class="card-header">
                    <h2 class="card-title">{{ t.title }}</h2>
                    <span class="badge" :class="t.isArchived ? 'badge-archived' : 'badge-active'">
            {{ t.isArchived ? 'Archivé' : 'Actif' }}
          </span>
                </div>
                <div class="card-body">
                    <div class="meta">
                        <div class="meta-item">
                            <span class="label">Messages</span>
                            <span class="value">{{ t.messagesCount }}</span>
                        </div>
                        <div class="meta-item">
                            <span class="label">Publié</span>
                            <span class="value">{{ formatDate(t.createdAt) }}</span>
                        </div>
                        <div class="meta-item">
                            <span class="label">Dernier message</span>
                            <span class="value">{{ formatDate(t.lastMessageAt) }}</span>
                        </div>
                    </div>
                    <div class="card-actions">
                        <RouterLink class="btn btn-primary" :to="`/forum/${t.id}`">Ouvrir</RouterLink>
                    </div>
                </div>
            </article>

            <div v-if="paged.length === 0" class="empty">
                Aucun topic ne correspond à votre recherche.
            </div>
        </div>

        <PaginationControls
            v-model:page="page"
            v-model:pageSize="pageSize"
            :totalPages="totalPages"
            @update:pageSize="setPage(1)"
        />
    </section>
</template>

<script>
import PaginationControls from "@/components/Pagination.vue";

export default {
    name: 'ForumPage',
    components: {PaginationControls},
    data() {
        return {
            allTopics: [
                { id: 1, title: 'Bienvenue sur le forum', messagesCount: 42, isArchived: false, createdAt: '2024-10-01T10:00:00Z', lastMessageAt: '2024-12-12T12:00:00Z' },
                { id: 2, title: 'Recherche joueurs à Paris', messagesCount: 15, isArchived: false, createdAt: '2024-11-05T08:30:00Z', lastMessageAt: '2025-01-10T09:20:00Z' },
                { id: 3, title: 'Tournoi local \- résultats', messagesCount: 87, isArchived: true, createdAt: '2023-05-20T14:10:00Z', lastMessageAt: '2023-06-01T18:45:00Z' },
                { id: 4, title: 'Astuces nouveaux joueurs', messagesCount: 23, isArchived: false, createdAt: '2024-12-20T16:00:00Z', lastMessageAt: '2025-01-15T11:11:00Z' },
                { id: 5, title: 'Règles du forum', messagesCount: 3, isArchived: true, createdAt: '2022-01-01T00:00:00Z', lastMessageAt: '2022-02-01T00:00:00Z' },
                { id: 6, title: 'Matchmaking hebdo', messagesCount: 51, isArchived: false, createdAt: '2024-07-12T12:00:00Z', lastMessageAt: '2025-01-03T13:00:00Z' },
            ],
            q: '',
            sortBy: 'lastMessageAt', // 'createdAt' | 'lastMessageAt' | 'messagesCount'
            sortDir: 'desc', // 'asc' | 'desc'
            stateFilter: 'all', // 'all' | 'active' | 'archived'
            page: 1,
            pageSize: 5,
        }
    },
    computed: {
        filtered() {
            const query = this.q.trim().toLowerCase()
            return this.allTopics.filter(t => {
                const matchesText = query === '' || t.title.toLowerCase().includes(query)
                const matchesState =
                    this.stateFilter === 'all' ||
                    (this.stateFilter === 'active' && !t.isArchived) ||
                    (this.stateFilter === 'archived' && t.isArchived)
                return matchesText && matchesState
            })
        },
        sorted() {
            const key = this.sortBy
            const dir = this.sortDir
            const arr = [...this.filtered]
            arr.sort((a, b) => {
                const va = key === 'messagesCount' ? a.messagesCount : Date.parse(a[key])
                const vb = key === 'messagesCount' ? b.messagesCount : Date.parse(b[key])
                return dir === 'asc' ? va - vb : vb - va
            })
            return arr
        },
        totalPages() {
            return Math.max(1, Math.ceil(this.sorted.length / this.pageSize))
        },
        paged() {
            const start = (this.page - 1) * this.pageSize
            return this.sorted.slice(start, start + this.pageSize)
        },
    },
    methods: {
        setPage(p) {
            this.page = Math.min(Math.max(1, p), this.totalPages)
        },
        onSearchInput() {
            this.setPage(1)
        },
        toggleSortDir() {
            this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc'
        },
        formatDate(iso) {
            const d = new Date(iso)
            return d.toLocaleString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            })
        },
    },
}
</script>

<style scoped>
</style>
