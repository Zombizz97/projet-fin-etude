<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Character;
use App\Models\UserCharacter;
use App\Models\ForumCategory;
use App\Models\ForumTopic;
use App\Models\ForumPost;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1) Users
        $users = User::factory()->count(5)->create();

        // 2) Characters via API Smash Bros Ultimate (API ONLY, pas de fallback)
        $characters = $this->seedUltimateCharactersFromApi();

        // 3) Associer 1 personnage à chaque user (choix aléatoire, marqué principal)
        $available = $characters->shuffle()->values();
        if ($available->isNotEmpty()) {
            foreach ($users as $index => $user) {
                $char = $available[$index % $available->count()];
                UserCharacter::create([
                    'user_id' => $user->id,
                    'character_id' => $char->id,
                    'is_main' => true,
                ]);
            }
        }

        // 4) Catégories de forum (10)
        $categories = ForumCategory::factory()->count(10)->create();

        // 5) Pour chaque catégorie: 3 topics
        foreach ($categories as $category) {
            $topics = ForumTopic::factory()
                ->count(3)
                ->state(function () use ($category, $users) {
                    return [
                        'category_id' => $category->id,
                        'user_id' => $users->random()->id,
                        'is_archived' => false,
                    ];
                })
                ->create();

            // 6) Pour chaque topic: 10 à 30 posts
            foreach ($topics as $topic) {
                $postCount = random_int(10, 30);
                ForumPost::factory()
                    ->count($postCount)
                    ->state(function () use ($topic, $users) {
                        return [
                            'topic_id' => $topic->id,
                            'user_id' => $users->random()->id,
                        ];
                    })
                    ->create();
            }
        }
    }

    /**
     * Récupère les personnages Ultimate depuis l'API et les insère/maj en base.
     * Retourne une collection de modèles Character.
     */
    protected function seedUltimateCharactersFromApi(): Collection
    {
        $endpoint = 'https://smashbrosapi.com/api/v1/ultimate/characters';
        try {
            $response = Http::withOptions(['verify' => false])
                ->timeout(20)
                ->retry(2, 500)
                ->get($endpoint);
            if (!$response->successful()) {
                // API uniquement: pas de fallback
                return collect();
            }
            $data = $response->json();
            if (!is_array($data)) {
                return collect();
            }
            $inserted = collect();
            foreach ($data as $item) {
                $name = Arr::get($item, 'name');
                $icon = Arr::get($item, 'images.icon');
                if (!$name) {
                    continue;
                }
                $character = Character::updateOrCreate(
                    ['name' => $name],
                    ['icon_path' => $icon]
                );
                $inserted->push($character);
            }
            return $inserted;
        } catch (\Throwable $e) {
            return collect();
        }
    }
}
