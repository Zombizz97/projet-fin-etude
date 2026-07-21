# Backend — SmashConnect

API Laravel 12 pour la plateforme SmashConnect.

## Stack

- Laravel 12
- PHP 8.2+
- SQLite (dev)
- Sanctum (auth)
- firebase/php-jwt
- Prometheus client PHP

## Installation

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
```

## Lancer le serveur

```bash
php artisan serve
```

L'API est accessible sur `http://localhost:8000`.

## Commandes utiles

| Commande | Description |
|---|---|
| `composer test` | Lance les tests PHPUnit |
| `composer lint` | Vérifie le style avec Laravel Pint |
| `composer dev` | Lance le serveur + queue + logs + Vite |

## Tests

```bash
composer test
```

Avec couverture :

```bash
composer test:coverage
```
