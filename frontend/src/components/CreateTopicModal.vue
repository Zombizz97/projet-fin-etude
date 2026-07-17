<template>
    <div v-if="visible" class="modal-backdrop" @click.self="$emit('close')">
        <div class="modal-panel">
            <div class="modal-header">
                <h2 class="modal-title">Nouveau sujet</h2>
                <button class="close-btn" @click="$emit('close')" aria-label="Fermer">&times;</button>
            </div>
            <form class="modal-body" @submit.prevent="submit">
                <div class="field">
                    <label class="label" for="cat-select">Catégorie</label>
                    <select id="cat-select" v-model="categoryId" class="select" required>
                        <option value="" disabled>Choisissez une catégorie</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="field">
                    <label class="label" for="topic-title">Titre</label>
                    <input id="topic-title" v-model="title" class="input" type="text"
                           placeholder="Titre du sujet" required maxlength="255" />
                </div>
                <div class="field">
                    <label class="label" for="topic-content">Contenu</label>
                    <textarea id="topic-content" v-model="content" class="textarea" rows="6"
                              placeholder="Écrivez votre message…" required></textarea>
                </div>
                <div v-if="error" class="error-msg">{{ error }}</div>
                <div class="modal-actions">
                    <button type="button" class="btn" @click="$emit('close')">Annuler</button>
                    <button type="submit" class="btn btn-primary" :disabled="sending">
                        {{ sending ? 'Création…' : 'Créer le sujet' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import api from '@/services/api'

export default {
    name: 'CreateTopicModal',
    props: {
        visible: { type: Boolean, default: false },
        categories: { type: Array, default: () => [] },
    },
    emits: ['close', 'created'],
    data() {
        return {
            categoryId: '',
            title: '',
            content: '',
            sending: false,
            error: null,
        }
    },
    methods: {
        async submit() {
            if (!this.categoryId || !this.title.trim() || !this.content.trim()) return
            this.sending = true
            this.error = null
            try {
                const res = await api.post('/forums', {
                    category_id: this.categoryId,
                    title: this.title,
                    content: this.content,
                })
                this.$emit('created', res.data)
                this.categoryId = ''
                this.title = ''
                this.content = ''
            } catch (e) {
                this.error = e.response?.data?.message || 'Erreur lors de la création.'
            } finally {
                this.sending = false
            }
        }
    },
    watch: {
        visible(v) {
            if (v) {
                this.categoryId = ''
                this.title = ''
                this.content = ''
                this.error = null
            }
        }
    }
}
</script>

<style scoped>
.modal-backdrop {
    position: fixed; inset: 0; background: rgba(0,0,0,0.5);
    display: flex; align-items: center; justify-content: center;
    z-index: 100; padding: 1rem;
}
.modal-panel {
    background: var(--color-bg-surface); border-radius: .75rem;
    width: 100%; max-width: 560px; max-height: 90vh; overflow-y: auto;
}
.modal-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 1rem 1.25rem; border-bottom: 1px solid var(--color-bg-surface-alt);
}
.modal-title { font-size: 1.15rem; font-weight: 700; }
.close-btn { background: transparent; border: none; font-size: 1.5rem; cursor: pointer; color: var(--color-text-primary); padding: .25rem; line-height: 1; }
.modal-body { padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: .35rem; }
.label { font-weight: 600; font-size: .9rem; }
.textarea { width: 100%; padding: .5rem; border: 1px solid var(--color-bg-surface); border-radius: .5rem; background: var(--color-bg-surface-alt); color: var(--color-text-primary); resize: vertical; font-family: inherit; }
.modal-actions { display: flex; gap: .5rem; justify-content: flex-end; margin-top: .5rem; }
.error-msg { color: #f44336; font-size: .85rem; }
</style>
