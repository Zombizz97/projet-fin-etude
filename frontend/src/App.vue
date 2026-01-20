<script setup>
import axios from 'axios'

axios.get('http://localhost:8000/api/test')
    .then(res => {
        console.log(res.data)
    })
</script>

<template>
  <h1>You did it!</h1>
  <p>
    Visit <a href="https://vuejs.org/" target="_blank" rel="noopener">vuejs.org</a> to read the
    documentation
  </p>
</template>

<style scoped></style>
