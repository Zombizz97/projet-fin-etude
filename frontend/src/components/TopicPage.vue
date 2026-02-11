<template>
    <section class="topic">
        <header class="topic-header">
            <h1 class="title">{{ topic?.title || 'Topic' }}</h1>
            <RouterLink class="btn" to="/forum">← Retour au forum</RouterLink>
            <div class="meta" v-if="topic">
                <span class="badge mb-3"
                      :class="topic.is_archived ? 'badge-archived' : 'badge-active'" >
                    {{ topic.is_archived ? 'Archivé' : 'Actif' }}
                </span>
                <span class="category">Dans: {{ topic.category?.name }}</span>
                <span class="author">Par: {{ topic.user?.username || topic.user?.name || 'Utilisateur' }}</span>
                <span class="count">Messages: {{ topic.posts_count ?? total }}</span>
            </div>
        </header>

        <div v-if="loading" class="loading">Chargement…</div>
        <div v-else>
            <article v-for="p in posts" :key="p.id" class="post">
                <div class="post-header">
                    <div class="author">
                        <strong>{{ p.user?.username || p.user?.name || 'Utilisateur' }}</strong>
                        <span class="date"> - {{ formatDate(p.created_at) }}</span>

                    </div>
                </div>
                <div class="post-body">
                    <p class="content" v-html="formatContent(p.content)"></p>
                </div>
            </article>
            <div v-if="posts.length === 0" class="empty">Aucun message pour ce topic.</div>
        </div>
        <PaginationControls
            v-if="lastPage > 1"
            v-model:page="page"
            v-model:pageSize="pageSize"
            :totalPages="lastPage"
            @update:page="onPageChange"
            @update:pageSize="onPageSizeChange" />
    </section>
</template>

<script>
import axios from 'axios'
import PaginationControls from '@/components/Pagination.vue'

export default {
    name: 'TopicPage',
    components: { PaginationControls },
    data() {
        return {
            topic: null,
            posts: [],
            loading: false,
            error: null,
            page: 1,
            pageSize: 10,
            lastPage: 1,
            total: 0,
        }
    },
    mounted() {
        this.fetchTopicMeta()
        this.fetchPosts()
    },
    watch: {
        '$route.params.id': function() {
            this.page = 1
            this.fetchTopicMeta()
            this.fetchPosts()
        }
    },
    methods: {
        async fetchTopicMeta() {
            const id = this.$route.params.id
            if (!id) return
            try {
                const res = await axios.get(`http://localhost:8000/api/forums/${id}`)
                const t = res.data || {}
                this.topic = t
                if (typeof t.posts_count === 'number') this.total = t.posts_count
            } catch (e) {
                console.error('Erreur lors du chargement des métadonnées du topic', e)
            }
        },
        async fetchPosts() {
            const id = this.$route.params.id
            if (!id) return
            this.loading = true
            this.error = null
            try {
                const res = await axios.get(`http://localhost:8000/api/forums/${id}/posts`, {
                    params: { page: this.page, per_page: this.pageSize }
                })
                const payload = res.data || {}
                const items = Array.isArray(payload.data) ? payload.data : []
                const meta = payload.meta || {}
                this.posts = items
                this.page = meta.current_page || this.page
                this.lastPage = meta.last_page || 1
                this.pageSize = meta.per_page || this.pageSize
                this.total = typeof meta.total === 'number' ? meta.total : this.total
            } catch (e) {
                this.error = 'Impossible de charger les messages.'
            } finally {
                this.loading = false
            }
        },
        onPageChange(p) {
            this.page = p
            this.fetchPosts()
        },
        onPageSizeChange(s) {
            this.pageSize = s
            this.page = 1
            this.fetchPosts()
        },
        formatDate(iso) {
            if (!iso) return '-'
            const d = new Date(iso)
            return d.toLocaleString('fr-FR', {
                year: 'numeric', month: 'long', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            })
        },
        formatContent(content) {
            if (!content) return ''
            return String(content)
                .replace(/\n/g, '<br/>')
                .replace(/\s{2,}/g, ' ')
        }
    }
}
</script>

<style scoped>
.meta { display: flex; gap: .75rem; color: var(--color-text-primary); }
.post { border: 1px solid var(--color-bg-surface); padding: .75rem; border-radius: .5rem; margin-bottom: .75rem; background: var(--color-bg-surface-alt); }
.post-header { display: flex; justify-content: space-between; margin-bottom: .5rem; }
.author { color: var(--color-accent-primary); }
.date { color: var(--color-text-primary); font-size: .85rem; }
.content { white-space: normal; }
.loading { color: #777; }
.empty { color: #777; font-style: italic; }
</style>
