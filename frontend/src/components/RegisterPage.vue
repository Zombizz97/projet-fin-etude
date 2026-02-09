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
                    <select
                        class="input"
                        id="main"
                        v-model="main"
                        required
                        :aria-invalid="!!errors.main"
                        aria-describedby="main-error"
                    >
                        <option value="" disabled>Choisir un personnage...</option>
                        <option v-for="c in characters" :key="c" :value="c">{{ c }}</option>
                    </select>
                    <p v-if="errors.main" id="main-error" class="error">{{ errors.main }}</p>
                </div>

                <div class="form-field">
                    <label for="level">Niveau</label>
                    <select
                        class="input"
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

                <button type="submit" class="btn btn-primary">Créer le compte</button>
            </form>
        </div>
    </section>
</template>

<script>
export default {
    name: 'RegisterPage',
    data() {
        return {
            pseudo: '',
            main: '',
            level: '',
            errors: { pseudo: '', main: '', level: '' },
            characters: ['Guerrier', 'Archer', 'Mage', 'Chaman', 'Rogue'],
            levels: ['Débutant', 'Intermédiaire', 'Confirmé', 'Professionnel']
        }
    },
    methods: {
        validate() {
            this.errors = { pseudo: '', main: '', level: '' }
            if (!this.pseudo.trim()) this.errors.pseudo = 'Pseudo requis.'
            if (!this.main) this.errors.main = 'Personnage principal requis.'
            if (!this.level) this.errors.level = 'Niveau requis.'
            return !this.errors.pseudo && !this.errors.main && !this.errors.level
        },
        onSubmit() {
            if (!this.validate()) return
            console.log('Register:', { pseudo: this.pseudo.trim(), main: this.main, level: this.level })
        }
    }
}
</script>

<style scoped>
</style>
