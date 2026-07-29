<p align="center">
  <img src=".github/assets/logo.png" alt="Magic Show" width="260" />
</p>

<h1 align="center">Magic Show — Backend API</h1>

<p align="center">
  <em>The domain, not the decoration — products, orders, carts, and the rules that hold them together.</em>
</p>

<p align="center">
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white" />
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white" />
  <img alt="Sanctum" src="https://img.shields.io/badge/Auth-Sanctum-4E5EE4" />
  <img alt="Security" src="https://img.shields.io/badge/composer_audit-clean-2ea44f" />
</p>

---

## What this is

An **API-only Laravel 12 service** for the Magic Show (Magic Shoe) e-commerce platform. It owns the
domain model — products, categories, orders, carts, wishlists, blog, page content, inventory — and
exposes it as a versioned JSON REST API under `/api/v1`. It renders **no HTML UI**: there is no
Blade admin panel, no session-based login screen, and no front-end asset build step here.

That's a deliberate design, not an oversight. This service used to ship a Blade-based admin panel
alongside the API — it was removed in full (32 controllers, 162 views, the entire asset pipeline)
once a standalone React admin console took over that job. What's left is a clean, focused API
surface that does one thing: serve data correctly, safely, and quickly.

This is one of three sibling projects in the Magic Show portfolio:

| Application | Stack | Talks to this service |
| --- | --- | --- |
| Storefront | Next.js | Not currently — it runs on mock data |
| Admin dashboard | React (separate app) | Not currently — it runs on mock data |

The mock-data front ends exist so each piece of the portfolio can be evaluated independently.
Wiring either of them to this real API is a contained, deliberate next step — the storefront's mock
service layer in particular exists specifically to make that swap small.

## Requirements

- PHP 8.2+
- Composer 2
- MySQL / MariaDB, or SQLite for local development
- Extensions typical for Laravel (`pdo`, `mbstring`, `openssl`, `fileinfo`, `gd`)

## Getting started

```bash
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link      # exposes storage/app/public at public/storage
php artisan serve
```

Set `DB_*`, `APP_URL`, and `FRONTEND_URL` in `.env`. `FRONTEND_URL` drives CORS, the Sanctum
stateful domains, and the URLs embedded in password-reset emails. `.env` is gitignored and never
committed — see **Secrets** below for what actually needs to go in it.

There is no `npm install` step here — this service ships no front-end assets of its own.

## API surface

Everything lives under the `/api/v1` prefix, declared entirely in
[`routes/api.php`](routes/api.php). `routes/web.php` intentionally contains only a small service
descriptor at `/` — there is nothing else to render server-side.

| Area | Endpoints |
| --- | --- |
| Health | `GET /api/v1/health` (Laravel's own liveness probe is separately at `/up`) |
| Auth | `POST /api/v1/auth/*` — register, login, logout, profile, password reset |
| Content | `GET /api/v1/home/*`, `/about/*`, `/shop/*`, `/blog/*`, `/stores`, `/contact/*` |
| Catalog | `GET /api/v1/products`, `/categories`, `/reviews` |
| Cart & checkout | `/api/v1/cart/*` — works for both guests and authenticated customers |
| Orders | `/api/v1/orders/*` — customer order history (auth required) |
| Wishlist | `/api/v1/wishlist/*` (auth required) |
| Public forms | `/api/v1/newsletter/*`, `/api/v1/contact/send-message` — rate-limited |
| Staff | `/api/v1/admin/orders/*` — requires an authenticated staff account with an admin role |

Full endpoint-by-endpoint reference: [`docs/api/API_DOCUMENTATION.md`](docs/api/API_DOCUMENTATION.md).

## Auth model

Authentication is **token-based, via Laravel Sanctum** — there is no session login UI. Clients send
`Authorization: Bearer <token>`.

Two distinct authenticatable models exist, on purpose, rather than one model with a role flag
bolted on:

- **`App\Models\Customer`** — storefront shoppers. Registers and logs in through `/api/v1/auth/*`,
  which returns a Sanctum personal access token. Customer-scoped routes (checkout, orders,
  wishlist) are guarded by `auth:sanctum`. Password resets use the `customers` broker and email a
  link pointing at `FRONTEND_URL`.
- **`App\Models\User`** — internal staff. Carries a role (`super_admin`, `store_manager`,
  `product_manager`, `analytics_team`, `customer_service`) plus a permission relationship.
  Staff-only endpoints stack `auth:sanctum` with the `admin.api` middleware
  (`App\Http\Middleware\EnsureAdminApi`), which requires a `super_admin` or `store_manager` token.

Rate limits (`App\Providers\AppServiceProvider`): `api` (120/min, 300 in local), `auth` (5/min),
`contact` and `newsletter` (3/hour), `comments` (10/hour). Request locale is negotiated by the
`set.language` middleware.

Two details worth calling out because they're the kind of thing a rushed build skips:
**password resets revoke every existing Sanctum token** for that customer, and
`POST /api/v1/auth/forgot-password` **always returns the same 200 response** whether or not the
address is registered — so the endpoint can't be used to enumerate accounts.

## Secrets

Nothing secret is committed. `.env` is gitignored; `.env.example` documents every key a real
deployment needs.

Firebase service-account credentials in particular must **never** be committed. Provide them at
runtime through either `FIREBASE_CREDENTIALS` (a path to a JSON file kept outside the repo) or
`FIREBASE_CREDENTIALS_JSON` (the raw JSON, for hosts without a persistent disk). If neither is set,
`FirebaseNotificationService::isConfigured()` returns `false` and new-order push notifications are
simply skipped — no error, no crash. See [FIREBASE_SETUP.md](FIREBASE_SETUP.md).

## Dependencies and security

This project tracks **Laravel 12** (`laravel/framework: ^12.61.1`) deliberately, not by default.

Every `laravel/framework` 11.x release carries open security advisories (a CRLF injection in the
default `email` validation rule, and a temporary signed-URL path-confusion issue). Composer refuses
the install for a flagged package by default, so on Laravel 11 a fresh clone of this project could
not even run `composer install` without silencing the warning.

The fix here was to **upgrade the framework, not suppress the warning** — every advisory is
resolved outright in `>= 12.61.1`. There is deliberately **no `config.audit.ignore` entry** in
`composer.json`: a clean clone installs with zero advisories, ignored or otherwise.

```bash
composer install     # clean — no advisory override required
composer audit        # "No security vulnerability advisories found."
```

Beyond the framework version itself, the application layer has its own regression coverage for the
things that actually matter in an e-commerce API: ownership checks on carts/orders/wishlists so one
customer can never read another's data, token revocation on password reset, race-condition
protection on stock decrement and coupon redemption (wrapped in DB transactions with row locking),
and bounded cache keys on public search endpoints so a malicious query string can't be used to
exhaust the cache. See `tests/Feature/SecurityRegressionTest.php`.

## Tests

```bash
php artisan test
```

## Documentation map

- [`docs/api/API_DOCUMENTATION.md`](docs/api/API_DOCUMENTATION.md) — full endpoint reference
- [`docs/guides/AUTHENTICATION_SYSTEM.md`](docs/guides/AUTHENTICATION_SYSTEM.md) — the auth model, in depth
- [`FIREBASE_SETUP.md`](FIREBASE_SETUP.md) — optional push notifications on new orders
- [`docs/GITHUB_SETUP.md`](docs/GITHUB_SETUP.md) — creating the remote and pushing for the first time
- [`docs/admin/BRD_MAGIC_SHOE_ADMIN_DASHBOARD.md`](docs/admin/BRD_MAGIC_SHOE_ADMIN_DASHBOARD.md) —
  kept as a historical record of the original business requirements behind the (since-removed)
  Blade admin panel and its React replacement

Everything else that once lived under `docs/` — status reports, stage-by-stage build notes, an
earlier "implementation complete" checkpoint — described a version of this project that no longer
exists. Rather than let stale documentation sit next to accurate documentation, it was retired
instead of kept "just in case."

## Framework

Built with [Laravel](https://laravel.com). Official docs: [laravel.com/docs](https://laravel.com/docs).

## License

The Laravel framework is open-sourced software licensed under the
[MIT license](https://opensource.org/licenses/MIT). Application-specific licensing is defined by
the project owner.
