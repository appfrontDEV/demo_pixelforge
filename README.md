# PixelForge

> Built in public. This README tracks every request made while building the project, so anyone can follow along.

## Stack

- **Laravel 13** (PHP 8.3) with the official Vue starter kit
- Inertia 3 + Vue 3 + TypeScript + Tailwind 4, Wayfinder for typed routes
- Laravel Fortify for authentication
- SQLite (default local database)
- Vite for frontend assets (Node.js ≥ 20.19)
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

Or run `composer run setup` to do the install, key, migrate and build steps in one go.

Run tests with `php artisan test`.

## Features

### Accounts

- Sign up with name, email and password, then log in and out.
- Reset a forgotten password by email link.
- Edit your profile, change your password, choose a light/dark/system appearance, and delete your account (confirming with your password).

### API tokens

- Create personal API tokens under **Settings → API tokens** to call the PixelForge API (`/api/v1`) from scripts. Each token is shown once, when it is created.
- See each token's name, creation date and last use, and revoke any token to stop it working at once.
- The same works over the API (`POST /api/v1/tokens` with your email and password, then `GET` and `DELETE`) and from the console (`php artisan api-tokens:create|list|revoke`). The API is documented in [`docs/openapi.yaml`](docs/openapi.yaml).

## Build log

Each entry summarizes one request and what was done about it.

### 001 · 2026-11-17 · Project kickoff

**Request:** Start the project as a new Laravel app, and summarize every request in the README to build in public.

**Done:**
- Scaffolded a fresh Laravel 13 application (no starter kit) via `composer create-project laravel/laravel`.
- Kept the repo's existing AGPL-3.0 `LICENSE` and set `composer.json` to match (`AGPL-3.0-or-later`).
- Merged Laravel's `.gitignore` entries into the existing one.
- Created this README with a running build log.

### 002 · 2026-11-17 · Specification issue

**Request:** Create a first issue with the specifications from the [PromptEdit assessment](https://pixelforge.promptedit.dev/assessment), to review the plan before writing the specs into `docs/`.

**Done:**
- Read the assessment brief and the full [Pixelforge API docs](https://pixelforge.promptedit.dev/docs).
- Opened [issue #1](https://github.com/appfrontDEV/demo_pixelforge/issues/1). It covers the required scope, stretch goals, a proposed money model, the robustness plan, a draft data model, setup tasks and open decisions.
- Next: review the plan, then write the final spec to `docs/`.

### 003 · 2026-11-17 · Split the spec into sub-issues

**Request:** Split issue #1 into sub-issues, one small PR each.

**Done:**
- Created 16 sub-issues linked to [#1](https://github.com/appfrontDEV/demo_pixelforge/issues/1), in dependency order: spec doc (#2), stack (#3), money (#4), Pixelforge client (#5), estimator (#6), generations model (#7), create flow (#8), resilience (#9), prompt form (#10), history (#11), webhook intake (#12), webhook processing (#13), reconcile job (#14), deployment (#15), and the stretch goals admin report (#16) and balances (#17).
- Each issue lists its dependencies, acceptance criteria, the tests to write, and the branch and commit conventions. Issues that need an open decision from #1 §7 are marked as blocked.

### 004 · 2026-11-17 · Apply the open decisions

**Request:** Apply the answers to the open decisions, given in a comment on #1.

**Done:**
- Money: the provider cost is kept in nano-USD so it matches Pixelforge exactly. Customers are charged in integer cents, always rounded up, with at least 1¢ when the cost is above zero.
- Estimate: shown from a baseline of real costs per size × quality, starting at $0–$1000.
- Hosting is Laravel Cloud. Images are copied to S3 (new #19). Access uses roles and permissions with a superadmin (new #18). Approved packages: `spatie/laravel-permission` and `league/flysystem-aws-s3-v3`.
- Updated sub-issues #2–#4, #6, #8, #11, #13 and #15–#17 to match.

### 005 · 2026-11-17 · Stack setup (#3)

**Request:** Start with sub-issue #3: add Inertia + Vue + TypeScript + Tailwind with authentication.

**Done:**
- Brought in the official Laravel Vue starter kit (Laravel 13, Inertia 3, Vue 3, Tailwind 4, Fortify, Wayfinder). Tests stay on PHPUnit 12.
- Kept registration and password confirmation. Left out email verification, two-factor and passkeys, so testers can sign up without mail.
- Kept the AGPL license, README and agent instruction files. Merged the kit's `.gitignore` entries.
- Clean-up: stopped tracking `database/database.sqlite` and a compiled view, and restored Laravel's `storage/**/.gitignore` files.

### 006 · 2026-11-17 · Money primitives (#4)

**Request:** Continue with sub-issue #4: exact money handling.

**Done:**
- `NanoUsd`: exact provider cost in integer nano-USD. It parses and formats the Pixelforge 9-decimal strings, such as `"0.373875000"`, and rejects anything a float could produce.
- `Cents`: what customers pay, in whole cents, with integer-only display formatting such as `$1,234.56`.
- `ProviderCostCalculator`: Σ(tokens × rate per 1M) from a generation's own `usage` and `rates`. It ignores `total_tokens` and refuses to bill anything it can't compute exactly.
- `CustomerPrice`: cost × 125%, always rounded up to whole cents in one integer step. The docs' worked maximum, $0.373875, costs the customer 47¢; a failed generation costs 0¢.
- Eloquent casts `AsNanoUsd` and `AsCents` for BIGINT columns. They reject floats on write, accept BIGINT-as-string on read, and serialize nano-USD as the 9-decimal string.

### 007 · 2026-11-18 · Generations model and state machine (#7)

**Request:** Start sub-issue #7: the generations data model and its forward-only status.

**Done:**
- `generations` table, `Generation` model and factory. Ids are UUIDs, and the Pixelforge `gen_…` id is unique. Columns cover the prompt, size and quality, up to 4 reference images, the estimate in cents, `usage`/`rates`, the exact provider cost in nano-USD, the customer price in cents, the image URL and its expiry, the error code and message, and the submitted/started/completed timestamps.
- Deleting an account keeps its generations (`user_id` is set to null), so provider costs still reconcile with `GET /v1/usage`.
- `GenerationStatus` enum: `pending → queued → processing → succeeded | failed`. Steps can be skipped, since a webhook can arrive before the create response. A status never moves back, and `succeeded`/`failed` are final.
- `transitionTo()` is the only way to change status (`status` isn't mass-assignable). It is a conditional `UPDATE`, so a stale copy or a late webhook can't move a finished generation back. Extra fields, such as cost and price, are written in the same statement. Repeating a transition is a no-op.
- Tests cover all 25 transitions, idempotent repeats, the stale-copy race, and writing cost and price with the move.
### 007 · 2026-11-18 · Pixelforge client (#5)

**Request:** Continue with sub-issue #5: the Pixelforge API client. The folder chosen is `app/Services/Pixelforge`.

**Done:**
- `PixelforgeClient`: the single gateway to the API. Base URL, key and timeouts come from `config/services.php` and `.env`, with one key and webhook secret per environment.
- `pricing()` reads `GET /v1/pricing`, which needs no key, and caches it as a plain array. Failures are never cached. `generation($id)` reads `GET /v1/generations/{id}` with the bearer key and refuses to run without one.
- Every documented `error.code` maps to its own exception class, which carries the HTTP status, `param`/`errors`, `Retry-After` and the rate-limit headers. Retryable errors (overloaded, rate and concurrency limits, network failures) share a `RetryableException` parent.
- Checked live against the real pricing endpoint.

### 008 · 2026-11-18 · Webhook tunnel with ngrok

**Request:** Create a webhook tunnel using ngrok, so Pixelforge can reach the local app.

**Done:**
- Upgraded the ngrok agent from 3.11 to 3.39 via Homebrew; the account now refuses agents older than 3.29.
- Opened a tunnel with `ngrok http 8000`, forwarding a public HTTPS URL to `php artisan serve`. Requests can be inspected at `http://localhost:4040`.
- No webhook route exists yet; the receiving endpoint comes with #12.

### 012 · 2026-11-18 · API tokens (#8, part A)

**Request:** Continue with sub-issue #8. Agreed order: API auth first (part A), then #6, then the create-generation flow (#8 part B). Sanctum approved.

**Done:**
- Installed Laravel Sanctum. The API is token-only: no cookie/SPA sessions on `/api`.
- Personal API tokens can be created, listed and revoked in three places that share the same actions: the new **Settings → API tokens** page, `/api/v1/tokens`, and `php artisan api-tokens:create|list|revoke`.
- `POST /api/v1/tokens` exchanges email and password for a token and allows 6 requests per minute. A wrong password and an unknown email return the same error.
- Users can only see and revoke their own tokens. Anyone else's token returns 404.
- Started [`docs/openapi.yaml`](docs/openapi.yaml), including the standard response shapes: `{data}`, `{message, errors}` and `{message}`.

