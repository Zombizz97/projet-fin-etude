<template>
    <footer class="pagination">
        <button class="btn" :disabled="page === 1" @click="emitPage(page - 1)">Préc.</button>
        <span class="page-indicator">Page {{ page }} / {{ totalPagesComputed }}</span>
        <button class="btn" :disabled="page === totalPagesComputed" @click="emitPage(page + 1)">Suiv.</button>

        <select class="select" :value="pageSize" @change="onPageSizeChange">
            <option v-for="n in pageSizeOptions" :key="n" :value="n">{{ n }}</option>
        </select>
    </footer>
</template>

<script>
export default {
    name: 'PaginationControls',
    props: {
        page: { type: Number, required: true },
        totalPages: { type: Number, required: true },
        pageSize: { type: Number, default: 10 },
        pageSizeOptions: { type: Array, default: () => [5, 10, 20] },
    },
    emits: ['update:page', 'update:pageSize'],
    computed: {
        totalPagesComputed() {
            return Math.max(1, this.totalPages || 1)
        },
    },
    methods: {
        clampPage(p) {
            return Math.min(Math.max(1, p), this.totalPagesComputed)
        },
        emitPage(p) {
            this.$emit('update:page', this.clampPage(p))
        },
        onPageSizeChange(e) {
            const v = Number(e.target.value)
            this.$emit('update:pageSize', v)
        },
    },
}
</script>

<style scoped>
</style>