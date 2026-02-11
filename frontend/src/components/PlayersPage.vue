<template>
    <section class="forum">
        <header class="forum-header">
            <h1 class="title">Joueurs</h1>
            <div class="actions grid grid-cols-12 gap-3 items-center">
                <div class="col-span-12 md:col-span-4">
                    <input
                        class="input"
                        type="text"
                        v-model="search"
                        placeholder="Rechercher par pseudo..." />
                </div>

                <div class="col-span-6 md:col-span-2">
                    <select v-model="filterLevel" class="select w-full select-fix">
                        <option value="">Tous</option>
                        <option v-for="lvl in levels" :key="lvl" :value="lvl">
                            {{ lvl }}
                        </option>
                    </select>
                </div>

                <div class="col-span-6 md:col-span-6">
                    <MultiSelect
                        v-model="filterCharacters"
                        :options="characterOptions"
                        optionLabel="name"
                        filter
                        placeholder="Filtrer par personnage..."
                        display="chip"
                        class="w-full md:w-80" >
                        <template #option="slotProps">
                            <div class="flex items-center gap-2">
                                <img
                                    :src="slotProps.option.icon || ''"
                                    class="w-5 h-5 object-contain"
                                    alt="" />
                                <span>{{ slotProps.option.name }}</span>
                            </div>
                        </template>
                    </MultiSelect>
                </div>
            </div>
        </header>

        <div class="cards">
            <div v-for="player in paginatedPlayers" :key="player.id" class="card">
                <div class="card-header">
                    <span class="pseudo">{{ player.username }}</span>
                    <span class="level">{{ player.skill_level || 'N/A' }}</span>
                </div>
                <div class="card-body">
                    <div class="characters">
                        <span v-for="ci in player.characterInfos" :key="ci.name" class="character-chip">
                            <span class="name">{{ ci.name }}</span>
                            <img v-if="ci.icon" :src="ci.icon" alt="icon" class="icon right" />
                        </span>
                    </div>
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
    import axios from 'axios'
    import MultiSelect from 'primevue/multiselect'

    export default {
    name: 'PlayersPage',
    components: { MultiSelect, Pagination },
    data() {
        return {
            search: '',
            filterLevel: '',
            filterCharacters: [],
            currentPage: 1,
            pageSize: 10,
            pageSizes: [5, 10, 20, 50],
            players: [],
            levels: [
                'débutant', 'intermédiaire', 'confirmé', 'professionnel'
            ],
            characterOptions: [],
        };
    },
    computed: {
        normalizedPlayers() {
            // Assure une structure uniforme pour l’affichage et les filtres
            return this.players.map(u => {
                const characterInfos = Array.isArray(u.characters)
                    ? u.characters.map(uc => ({
                        name: uc.character?.name,
                        icon: uc.character?.icon_path || null,
                    })).filter(ci => !!ci.name)
                    : []
                return {
                    id: u.id,
                    username: u.username || u.name || '',
                    skill_level: u.skill_level || '',
                    characterInfos,
                }
            })
        },
        filteredPlayers() {
            const searchLower = this.search.trim().toLowerCase();
            const selectedChars = this.filterCharacters.map(c => c.name.toLowerCase())
            return this.normalizedPlayers.filter(p => {
                const names = p.characterInfos.map(ci => ci.name)
                const matchSearch = !searchLower ||
                    p.username.toLowerCase().includes(searchLower) ||
                    names.some(n => n.toLowerCase().includes(searchLower))
                const matchLevel = !this.filterLevel || p.skill_level === this.filterLevel
                const matchCharacters = selectedChars.length === 0 || selectedChars.every(sel => names.map(n => n.toLowerCase()).includes(sel))
                return matchSearch && matchLevel && matchCharacters
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
        filterCharacters() { this.currentPage = 1; },
        pageSize() { this.currentPage = 1; },
    },
    methods: {
        goToPage(page) {
            if (page < 1) page = 1;
            if (page > this.totalPages) page = this.totalPages;
            this.currentPage = page;
        },
        async loadPlayers() {
            try {
                const res = await axios.get('http://localhost:8000/api/players')
                this.players = Array.isArray(res.data) ? res.data : []
            } catch (e) {
                console.error('Erreur de chargement des joueurs', e)
            }
        },
        async loadCharacters() {
            try {
                const res = await axios.get('http://localhost:8000/api/characters')
                const chars = Array.isArray(res.data) ? res.data : []
                this.characterOptions = chars.map(c => ({ name: c.name, icon: c.icon_path || null }))
            } catch (e) {
                console.error('Erreur de chargement des personnages', e)
            }
        }
    },
    async mounted() {
        await Promise.all([
            this.loadPlayers(),
            this.loadCharacters(),
        ])
    },
};
</script>

<style scoped>
.icon { width: 20px; height: 20px; object-fit: contain; }
.icon.right { margin-left: 6px; }
</style>
