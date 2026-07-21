# SmashConnect

Application full-stack de de mise en relation de joueurs de **Super Smash Bros. Ultimate**.

## Stack

- **Backend** : Laravel 12 (PHP 8.2, SQLite, Sanctum)
- **Frontend** : Vue 3 (Vite, Pinia, Vue Router, PrimeVue, Tailwind CSS 4)
- **Monitoring** : Prometheus, Grafana, Alertmanager

## Prérequis

- PHP 8.2+
- Composer
- Node.js 20+
- npm

## Lancer le projet en local

### 1. Backend

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

Le serveur backend tourne sur `http://localhost:8000`.

### 2. Frontend

```bash
cd frontend
npm install
npm run dev
```

Le frontend tourne sur `http://localhost:5173`.

### 3. Monitoring (optionnel)

```bash
docker compose -f docker-compose.monitoring.yml up -d
```

- Prometheus : `http://localhost:9090`
- Grafana : `http://localhost:3000` (admin:admin)
- Alertmanager : `http://localhost:9093`

## Tests

```bash
# Backend (PHPUnit)
cd backend && composer test

# Frontend (unitaires)
cd frontend && npm run test:unit

# Frontend (e2e)
cd frontend && npm run test:e2e
```
