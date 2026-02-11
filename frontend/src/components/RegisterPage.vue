<template>
    <section class="hero"
             :style="{ backgroundImage: 'url(/img/ultimate.jpg)' }">
        <div class="login-page">
            <form class="login-form" @submit.prevent="onSubmit" novalidate>
                <div class="form-field">
                    <label for="pseudo">Pseudo</label>
                    <input
                        class="input"
                        id="pseudo"
                        type="text"
                        v-model="pseudo"
                        placeholder="Votre pseudo"
                        required
                        :aria-invalid="!!errors.pseudo"
                        aria-describedby="pseudo-error"
                    />
                    <p v-if="errors.pseudo" id="pseudo-error" class="error">{{ errors.pseudo }}</p>
                </div>

                <div class="form-field">
                    <label for="main">Personnage principal</label>
                    <Select
                        inputId="main"
                        v-model="main"
                        :options="characterOptions"
                        optionLabel="name"
                        placeholder="Choisir un personnage..."
                        class="w-full"
                        :class="{'p-invalid': !!errors.main}"
                    >
                        <template #option="slotProps">
                            <div class="flex items-center gap-2">
                                <img :src="slotProps.option.icon" class="w-5 h-5 object-contain" alt="" />
                                <span>{{ slotProps.option.name }}</span>
                            </div>
                        </template>
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex items-center gap-2">
                                <img :src="slotProps.value.icon" class="w-5 h-5 object-contain" alt="" />
                                <span>{{ slotProps.value.name }}</span>
                            </div>
                            <span v-else>Choisir un personnage...</span>
                        </template>
                    </Select>
                    <p v-if="errors.main" id="main-error" class="error">{{ errors.main }}</p>
                </div>

                <div class="form-field">
                    <label for="level">Niveau</label>
                    <select
                        class="select w-full select-fix"
                        id="level"
                        v-model="level"
                        required
                        :aria-invalid="!!errors.level"
                        aria-describedby="level-error"
                    >
                        <option value="" disabled>Choisir un niveau...</option>
                        <option v-for="lvl in levels" :key="lvl" :value="lvl">{{ lvl }}</option>
                    </select>
                    <p v-if="errors.level" id="level-error" class="error">{{ errors.level }}</p>
                </div>

                <div class="form-field">
                    <label for="password">Mot de passe</label>
                    <input
                        class="input"
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        v-model="password"
                        placeholder="Votre mot de passe"
                        autocomplete="new-password"
                        required
                        :aria-invalid="!!errors.password"
                        aria-describedby="password-error"
                    />
                    <p v-if="errors.password" id="password-error" class="error">{{ errors.password }}</p>
                </div>

                <div class="form-field">
                    <label for="confirm">Confirmer le mot de passe</label>
                    <input
                        class="input"
                        id="confirm"
                        :type="showPassword ? 'text' : 'password'"
                        v-model="confirm"
                        placeholder="Confirmez le mot de passe"
                        autocomplete="new-password"
                        required
                        :aria-invalid="!!errors.confirm"
                        aria-describedby="confirm-error"
                    />
                    <p v-if="errors.confirm" id="confirm-error" class="error">{{ errors.confirm }}</p>
                </div>

                <div class="form-field">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" v-model="showPassword" />
                        <span>Afficher le mot de passe</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">Créer le compte</button>
            </form>
        </div>
    </section>
</template>

<script>
import Select from 'primevue/select'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

export default {
    name: 'RegisterPage',
    components: { Select },
    data() {
        return {
            pseudo: '',
            main: null,
            level: '',
            password: '',
            confirm: '',
            showPassword: false,
            errors: { pseudo: '', main: '', level: '', password: '', confirm: '' },
            characterOptions: [],
            levels: ['Débutant', 'Intermédiaire', 'Confirmé', 'Professionnel']
        }
    },
    computed: {
        apiSkillLevel() {
            const map = {
                'Débutant': 'débutant',
                'Intermédiaire': 'intermédiaire',
                'Confirmé': 'confirmé',
                'Professionnel': 'professionnel',
            }
            return map[this.level] || null
        }
    },
    methods: {
        validate() {
            this.errors = { pseudo: '', main: '', level: '', password: '', confirm: '' }
            if (!this.pseudo.trim()) this.errors.pseudo = 'Pseudo requis.'
            if (!this.main) this.errors.main = 'Personnage principal requis.'
            if (!this.level) this.errors.level = 'Niveau requis.'
            if (!this.password) this.errors.password = 'Mot de passe requis.'
            else if (this.password.length < 6) this.errors.password = 'Au moins 6 caractères.'
            if (!this.confirm) this.errors.confirm = 'Confirmation requise.'
            else if (this.confirm !== this.password) this.errors.confirm = 'Les mots de passe ne correspondent pas.'
            return Object.values(this.errors).every(v => !v)
        },
        async loadCharacters() {
            try {
                const res = await api.get('/characters')
                const chars = Array.isArray(res.data) ? res.data : []
                this.characterOptions = chars.map(c => ({ id: c.id, name: c.name, icon: c.icon_path || '' }))
            } catch (e) {
                console.error('Erreur de chargement des personnages', e)
                this.characterOptions = []
            }
        },
        async onSubmit() {
            if (!this.validate()) return
            const auth = useAuthStore()
            try {
                const payload = {
                    username: this.pseudo.trim(),
                    password: this.password,
                    skill_level: this.apiSkillLevel,
                    character_id: this.main?.id || null,
                }
                const { data } = await api.post('/auth/register', payload)
                if (data?.token) {
                    auth.setToken(data.token)
                }
                auth.user = data.user
                this.$router.push('/')
            } catch (e) {
                const msg = e?.response?.data?.message || 'Échec de création du compte'
                alert(msg)
            }
        }
    },
    async mounted() {
        await this.loadCharacters()
    }
}
</script>

<style scoped>
</style>
