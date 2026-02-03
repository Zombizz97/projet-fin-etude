<script setup>
import axios from 'axios'
import {ref} from "vue";
import Navbar from "@/components/Navbar.vue";

axios.get('http://localhost:8000/api/test')
    .then(res => {
        console.log(res.data)
    })

const isAuthenticated = ref(false)
const user = ref(null)
// isAuthenticated.value = true
// user.value = { pseudo: 'Zombizz97', avatarUrl: 'https://i.pravatar.cc/64?img=3' }

</script>

<template>
    <Navbar :is-authenticated="isAuthenticated"
            :user="user" />
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Bienvenue sur SmashConnect</h1>
            <p class="hero-subtitle">
                Rejoignez la communauté, discutez sur le forum et trouvez des joueurs près de chez vous.
            </p>
            <div class="hero-actions">
                <RouterLink to="/register" class="btn btn-primary">Créer un compte</RouterLink>
                <RouterLink to="/forum" class="btn">Accéder au forum</RouterLink>
            </div>
        </div>
    </section>
</template>

<style scoped></style>
