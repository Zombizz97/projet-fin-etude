<template>
  <section class="container">
    <h1>Mon profil</h1>

    <form class="form" @submit.prevent="onSubmit" novalidate>
      <div class="form-field">
        <label for="username">Pseudo</label>
        <input id="username" v-model="form.username" type="text" class="input" required />
      </div>

      <div class="form-field">
        <label for="skill_level">Niveau</label>
        <select id="skill_level" v-model="form.skill_level" class="input">
          <option value="">—</option>
          <option value="débutant">Débutant</option>
          <option value="intermédiaire">Intermédiaire</option>
          <option value="confirmé">Confirmé</option>
          <option value="professionnel">Professionnel</option>
        </select>
      </div>

      <div class="form-field">
        <label for="password">Nouveau mot de passe (optionnel)</label>
        <input id="password" v-model="form.password" type="password" class="input" placeholder="••••••" />
      </div>

      <div class="actions">
        <button type="submit" class="btn btn-primary" :disabled="loading">Sauvegarder</button>
        <span v-if="message" class="message">{{ message }}</span>
      </div>
    </form>
  </section>
</template>

<script>
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

export default {
  name: 'ProfilePage',
  data() {
    const auth = useAuthStore()
    return {
      loading: false,
      message: '',
      form: {
        username: auth.user?.username || '',
        skill_level: auth.user?.skill_level || '',
        password: '',
      },
    }
  },
  methods: {
    async onSubmit() {
      this.loading = true
      this.message = ''
      try {
        const payload = {
          username: this.form.username,
          skill_level: this.form.skill_level || null,
        }
        if (this.form.password) payload.password = this.form.password
        const { data } = await api.put('/user', payload)
        const auth = useAuthStore()
        auth.user = data.user
        this.message = 'Profil mis à jour'
      } catch (e) {
        this.message = e?.response?.data?.message || 'Erreur lors de la mise à jour'
      } finally {
        this.loading = false
      }
    }
  },
  async mounted() {
    const auth = useAuthStore()
    if (!auth.isAuthenticated) {
      this.$router.replace('/login')
      return
    }
    await auth.fetchMe()
    this.form.username = auth.user?.username || ''
    this.form.skill_level = auth.user?.skill_level || ''
  }
}
</script>

<style scoped>
.container { max-width: 720px; margin: 24px auto; padding: 0 16px; }
h1 { font-size: 1.5rem; margin-bottom: 16px; }
.form { display: flex; flex-direction: column; gap: 12px; }
.form-field { display: flex; flex-direction: column; gap: 6px; }
.input { padding: 10px 12px; border: 1px solid var(--color-bg-surface-alt); border-radius: 8px; background: var(--color-bg-surface); color: var(--color-text-primary); }
.actions { display: flex; align-items: center; gap: 12px; }
.message { font-size: .95rem; color: var(--color-text-primary); }
</style>

