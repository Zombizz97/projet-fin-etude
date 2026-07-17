<template>
    <section class="topic">
        <header class="topic-header">
            <div class="topic-header-top">
                <RouterLink class="btn" to="/forum">← Retour au forum</RouterLink>
                <h1 class="title">{{ topic?.title || 'Topic' }}</h1>
            </div>
            <div class="meta" v-if="topic">
                <span class="badge" :class="topic.is_archived ? 'badge-archived' : 'badge-active'">
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
                <div class="post-vote">
                    <button class="vote-btn" :class="{ active: p.user_vote === 'up' }"
                            :disabled="!isAuthenticated || topic?.is_archived"
                            @click="vote(p.id, 'up')" title="Upvote">▲</button>
                    <span class="vote-score" :class="{ positive: p.vote_balance > 0, negative: p.vote_balance < 0 }">
                        {{ p.vote_balance }}
                    </span>
                    <button class="vote-btn" :class="{ active: p.user_vote === 'down' }"
                            :disabled="!isAuthenticated || topic?.is_archived"
                            @click="vote(p.id, 'down')" title="Downvote">▼</button>
                </div>
                <div class="post-content">
                    <div class="post-header">
                        <strong>{{ p.user?.username || p.user?.name || 'Utilisateur' }}</strong>
                        <span class="date"> - {{ formatDate(p.created_at) }}</span>
                    </div>
                    <div class="post-body">
                        <p class="content" v-html="formatContent(p.content)"></p>
                    </div>
                </div>
            </article>
            <div v-if="posts.length === 0" class="empty">Aucun message pour ce topic.</div>

            <div v-if="topic && !topic.is_archived" class="reply-section">
                <h2 class="reply-title">Répondre</h2>
                <div v-if="!isAuthenticated" class="reply-login">
                    <RouterLink to="/login">Connectez-vous</RouterLink> pour répondre.
                </div>
                <form v-else class="reply-form" @submit.prevent="submitReply">
                    <textarea v-model="replyContent" class="textarea" rows="4"
                              placeholder="Écrivez votre message…" required></textarea>
                    <div class="reply-actions">
                        <button type="submit" class="btn btn-primary" :disabled="sending">
                            {{ sending ? 'Envoi…' : 'Envoyer' }}
                        </button>
                    </div>
                    <div v-if="replyError" class="error-msg">{{ replyError }}</div>
                </form>
            </div>
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
import api from '@/services/api'
import PaginationControls from '@/components/Pagination.vue'
import { useAuthStore } from '@/stores/auth'

export default {
    name: 'TopicPage',
    components: { PaginationControls },
    data() {
        return {
            topic: null,
            posts: [],
            loading: true,
            error: null,
            page: 1,
            pageSize: 10,
            lastPage: 1,
            total: 0,
            replyContent: '',
            sending: false,
            replyError: null,
        }
    },
    computed: {
        isAuthenticated() {
            return useAuthStore().isAuthenticated
        }
    },
    mounted() {
        this.load()
    },
    watch: {
        '$route.params.id': function () {
            this.page = 1
            this.load()
        }
    },
    methods: {
        load() {
            this.fetchTopicMeta()
            this.fetchPosts()
        },
        async fetchTopicMeta() {
            const id = this.$route.params.id
            if (!id) return
            try {
                const res = await api.get(`/forums/${id}`)
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
                const res = await api.get(`/forums/${id}/posts`, {
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
        async vote(postId, type) {
            try {
                const res = await api.post(`/posts/${postId}/vote`, { vote: type })
                const post = this.posts.find(p => p.id === postId)
                if (post) {
                    post.vote_balance = res.data.vote_balance
                    post.user_vote = res.data.user_vote
                }
            } catch (e) {
                console.error('Erreur lors du vote', e)
            }
        },
        async submitReply() {
            const id = this.$route.params.id
            if (!id || !this.replyContent.trim()) return
            this.sending = true
            this.replyError = null
            try {
                const res = await api.post(`/forums/${id}/posts`, { content: this.replyContent })
                this.posts.push(res.data)
                this.replyContent = ''
                this.total++
                this.lastPage = Math.ceil(this.total / this.pageSize)
                this.page = this.lastPage
            } catch (e) {
                this.replyError = e.response?.data?.message || 'Erreur lors de l\'envoi.'
            } finally {
                this.sending = false
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
.meta { display: flex; gap: .75rem; color: var(--color-text-primary); flex-wrap: wrap; }
.post { display: flex; gap: .75rem; border: 1px solid var(--color-bg-surface); padding: .75rem; border-radius: .5rem; margin-bottom: .75rem; background: var(--color-bg-surface-alt); }
.post-content { flex: 1; min-width: 0; }
.post-header { display: flex; gap: .5rem; margin-bottom: .5rem; }
.post-header strong { color: var(--color-accent-primary); }
.date { color: var(--color-text-primary); font-size: .85rem; }
.content { white-space: normal; }
.loading { color: #777; }
.empty { color: #777; font-style: italic; }
.post-vote { display: flex; flex-direction: column; align-items: center; gap: .25rem; min-width: 2.5rem; }
.vote-btn { background: transparent; border: 1px solid var(--color-bg-surface); border-radius: .25rem; padding: .25rem .5rem; cursor: pointer; font-size: .85rem; color: var(--color-text-primary); line-height: 1; }
.vote-btn:hover:not(:disabled) { background: var(--color-bg-surface); }
.vote-btn:disabled { opacity: .4; cursor: not-allowed; }
.vote-btn.active { color: var(--color-accent-primary); border-color: var(--color-accent-primary); }
.vote-score { font-weight: 700; font-size: .9rem; }
.vote-score.positive { color: #4caf50; }
.vote-score.negative { color: #f44336; }
.reply-section { margin-top: 1.5rem; border-top: 1px solid var(--color-bg-surface); padding-top: 1rem; }
.reply-title { font-size: 1.1rem; margin-bottom: .75rem; }
.reply-form { display: flex; flex-direction: column; gap: .5rem; }
.textarea { width: 100%; padding: .5rem; border: 1px solid var(--color-bg-surface); border-radius: .5rem; background: var(--color-bg-surface-alt); color: var(--color-text-primary); resize: vertical; font-family: inherit; }
.reply-actions { display: flex; gap: .5rem; }
.reply-login { color: var(--color-text-primary); font-style: italic; }
.error-msg { color: #f44336; font-size: .85rem; }
</style>
