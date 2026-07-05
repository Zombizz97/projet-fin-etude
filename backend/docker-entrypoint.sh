#!/bin/bash
php artisan migrate --force

php artisan tinker --execute='if (\App\Models\User::count() === 0) { echo "seeding..."; \Illuminate\Support\Facades\Artisan::call("db:seed", ["--force" => true]); }'

php artisan storage:link
apache2-foreground
