<template>
    <section class="forum">
        <header class="forum-header">
            <h1 class="title">Joueurs</h1>
            <div class="actions">
                <div class="search">
                    <input
                        class="input"
                        type="text"
                        v-model="search"
                        placeholder="Rechercher par pseudo..."
                    />
                </div>

                <div class="filters">
                    <select v-model="filterLevel" class="select">
                        <option value="">Tous les niveaux</option>
                        <option v-for="lvl in levels" :key="lvl" :value="lvl">{{ lvl }}</option>
                    </select>

                    <multiselect class="select"
                                 id="single-select-search"
                                 v-model="filterCharacter"
                                 :options="options"
                                 :multiple="true"
                                 :close-on-select="false"
                                 :clear-on-select="false"
                                 :preserve-search="true"
                                 placeholder="Filtrer par personnage..."
                                 label="name"
                                 track-by="name">
                    </multiselect>

                </div>
            </div>
        </header>

        <div class="cards">
            <div v-for="player in paginatedPlayers" :key="player.id" class="card">
                <div class="card-header">
                    <span class="pseudo">{{ player.pseudo }}</span>
                    <span class="level">{{ player.level }}</span>
                </div>
                <div class="card-body">
                    <span class="character">Personnage: {{ player.character }}</span>
                </div>
            </div>

            <div v-if="paginatedPlayers.length === 0" class="empty">
                Aucun joueur trouvé.
            </div>
        </div>

        <Pagination
            v-model:page="currentPage"
            v-model:pageSize="pageSize"
            :totalPages="totalPages"
            @update:pageSize="goToPage(1)"
        />
    </section>
</template>

<script>
import Pagination from './Pagination.vue'
import Multiselect from "vue-multiselect";

export default {
    name: 'PlayersPage',
    components: { Multiselect, Pagination },
    data() {
        return {
            search: '',
            filterLevel: '',
            filterCharacter: [],
            charOpen: false,
            currentPage: 1,
            pageSize: 10,
            pageSizes: [5, 10, 20, 50],
            players: [
                { id: 1, pseudo: 'Arthas', level: 'Débutant', character: 'Guerrier' },
                { id: 2, pseudo: 'Sylvanas', level: 'Débutant', character: 'Archer' },
                { id: 3, pseudo: 'Jaina', level: 'Intermédiaire', character: 'Mage' },
                { id: 4, pseudo: 'Thrall', level: 'Intermédiaire', character: 'Chaman' },
                { id: 5, pseudo: 'Valeera', level: 'Intermédiaire', character: 'Rogue' },
                { id: 6, pseudo: 'Anduin', level: 'Avancé', character: 'Prêtre' },
                { id: 7, pseudo: 'Illidan', level: 'Avancé', character: 'Démoniste' },
                { id: 8, pseudo: 'Rexxar', level: 'Avancé', character: 'Chasseur' },
                { id: 9, pseudo: 'Tyrande', level: 'Avancé', character: 'Druide' },
                { id: 10, pseudo: 'Uther', level: 'Expert', character: 'Paladin' },
                { id: 11, pseudo: 'Maiev', level: 'Expert', character: 'Rogue' },
                { id: 12, pseudo: 'Kael\'thas', level: 'Expert', character: 'Mage' },
            ],
            levels: [
                'Débutant', 'Intermédiaire', 'Confirmé', 'Professionnel'
            ],
            options: [
                {name: 'Guerrier'},
                {name: 'Archer'},
                {name: 'Mage'},
                {name: 'Chaman'},
                {name: 'Rogue'}
            ]
        };
    },
    computed: {
        filteredPlayers() {
            const searchLower = this.search.trim().toLowerCase();
            // const characterFilter = this.filterCharacter ? this.filterCharacter.name.toLowerCase() : '';
            return this.players.filter(p => {
                const matchSearch = !searchLower || p.pseudo.toLowerCase().includes(searchLower);
                const matchLevel = !this.filterLevel || p.level === this.filterLevel;
                // const matchCharacter = !characterFilter || p.character.toLowerCase().includes(characterFilter);
                return matchSearch && matchLevel;
            });
        },
        totalPages() {
            if (this.filteredPlayers.length === 0) return 0;
            return Math.ceil(this.filteredPlayers.length / this.pageSize);
        },
        paginatedPlayers() {
            const start = (this.currentPage - 1) * this.pageSize;
            return this.filteredPlayers.slice(start, start + this.pageSize);
        },
    },
    watch: {
        search() { this.currentPage = 1; },
        filterLevel() { this.currentPage = 1; },
        filterCharacter() { this.currentPage = 1; },
        pageSize() { this.currentPage = 1; },
    },
    methods: {
        goToPage(page) {
            if (page < 1) page = 1;
            if (page > this.totalPages) page = this.totalPages;
            this.currentPage = page;
        },
        handleOutsideClick(e) {
            const root = this.$refs.charSelect;
            if (root && !root.contains(e.target)) {
                this.charOpen = false;
            }
        }
    },
    mounted() {
        document.addEventListener('click', this.handleOutsideClick);
    },
    beforeDestroy() {
        document.removeEventListener('click', this.handleOutsideClick);
    },
};
</script>

<style scoped>
</style>
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
