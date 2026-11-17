# PixelForge

> Built in public. This README tracks every request made while building the project, so anyone can follow along.

## Stack

- **Laravel 13** (PHP 8.3)
- SQLite (default local database)
- Vite for frontend assets
- License: AGPL-3.0

## Getting started

```sh
git clone https://github.com/appfrontDEV/demo_pixelforge.git
cd demo_pixelforge
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install && npm run build
composer run dev   # or: php artisan serve
```

Run tests with `php artisan test`.

## Build log

Each entry summarizes one request and what was done about it.

### 001 · 2026-11-17 · Project kickoff

**Request:** Start the project as a new Laravel app, and summarize every request in the README to build in public.

**Done:**
- Scaffolded a fresh Laravel 13 application (no starter kit) via `composer create-project laravel/laravel`.
- Kept the repo's existing AGPL-3.0 `LICENSE` and set `composer.json` to match (`AGPL-3.0-or-later`).
- Merged Laravel's `.gitignore` entries into the existing one.
- Created this README with a running build log.
