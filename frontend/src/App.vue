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
    <RouterView />
</template>

<style scoped>
</style>
