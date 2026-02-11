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
                    autocomplete="username"
                    required
                    :aria-invalid="!!errors.pseudo"
                    aria-describedby="pseudo-error"
                />
                <p v-if="errors.pseudo" id="pseudo-error" class="error">{{ errors.pseudo }}</p>
            </div>

            <div class="form-field">
                <label for="password">Mot de passe</label>
                <div class="password-wrapper">
                    <input
                        class="input"
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        v-model="password"
                        placeholder="Votre mot de passe"
                        autocomplete="current-password"
                        required
                        :aria-invalid="!!errors.password"
                        aria-describedby="password-error"
                    />
                    <button
                        type="button"
                        class="btn toggle-password"
                        @click="togglePassword"
                        aria-label="Afficher/masquer le mot de passe"
                    >
                        {{ showPassword ? 'Masquer' : 'Afficher' }}
                    </button>
                </div>
                <p v-if="errors.password" id="password-error" class="error">{{ errors.password }}</p>
                <p v-if="apiError" id="password-error" class="error">{{ apiError }}</p>
            </div>

            <button type="submit" class="btn btn-primary">Se connecter</button>
        </form>
        </div>
    </section>
</template>

<script>
    import {useAuthStore} from '../stores/auth'

    export default {
    name: 'LoginPage',
    data() {
        return {
            pseudo: '',
            password: '',
            showPassword: false,
            errors: { pseudo: '', password: '' },
            loading: false,
            apiError: ''
        }
    },
    methods: {
        validate() {
            this.errors = { pseudo: '', password: '' }
            if (!this.pseudo) this.errors.pseudo = 'Pseudo requis.'
            if (!this.password) this.errors.password = 'Mot de passe requis.'
            return !this.errors.pseudo && !this.errors.password
        },
        async onSubmit() {
            if (!this.validate()) return
            this.loading = true
            this.apiError = ''
            try {
                const auth = useAuthStore()
                await auth.login({ username: this.pseudo, password: this.password })
                this.$router.push('/')
            } catch (e) {
                this.apiError = e?.response?.data?.message || 'Échec de connexion'
            } finally {
                this.loading = false
            }
        },
        togglePassword() {
            this.showPassword = !this.showPassword
        }
    }
}
</script>

<style scoped>
</style>
