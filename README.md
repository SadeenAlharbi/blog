# منصة المعرفة السعودية — Saudi Knowledge Platform

[![Tests](https://github.com/SadeenAlharbi/blog/actions/workflows/tests.yml/badge.svg)](https://github.com/SadeenAlharbi/blog/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

A full-stack Laravel + React knowledge platform for structured content about Saudi Arabia — history,
Vision 2030, national projects, the economy, technology and AI, culture, heritage, tourism, and society.
Built as a portfolio-grade demonstration of Laravel architecture, REST API design, authentication and
authorization, testing, and a Blade + React frontend — not a typical blog scaffold.

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Architecture Notes](#architecture-notes)
- [Installation](#installation)
- [Environment](#environment)
- [Database](#database)
- [Storage](#storage)
- [Authentication](#authentication)
- [Queue](#queue)
- [Testing](#testing)
- [API Reference](#api-reference)
- [Frontend](#frontend)
- [Running Locally](#running-locally)
- [Running with Docker](#running-with-docker)
- [License](#license)

## Features

- **Post CRUD** with slugs, image uploads, tags, and owner-only edit/delete — enforced via `PostPolicy`
  both in the REST API and the Blade dashboard, not just hidden buttons.
- **Versioned REST API** (`/api/v1`) with consistent JSON responses, API Resources, pagination metadata,
  search, tag filtering, and sorting.
- **Sanctum token authentication**: register, login, logout, authenticated profile.
- **Comments** with server-side validation and, on each new comment, a **queued dual-channel notification**
  to the post owner — an in-site bell notification (`database`) and an Arabic/RTL branded email (`mail`).
  The comment request returns immediately; both are delivered by the queue worker.
- **In-site notification bell** with an unread badge, a recent-notifications dropdown, mark-as-read /
  mark-all-as-read, an empty state, and a full notifications page — RTL and matching the site design.
- **Tag system** built on the existing `posts`/`tags`/`post_tag` schema — create, attach, filter, search.
- **Image uploads** via Laravel Storage (`public` disk, `storage:link`), validated by type and size.
- **Blade + React frontend**: server-rendered, SEO-friendly pages for content and forms; a real React
  island (Platforms Code UI components) for interactive search/filter on the posts listing.
- **Arabic-first, RTL, Saudi-modern design** — a moderate green brand palette (not an all-green theme),
  warm neutrals, and Tajawal typography.
- **Pest test suite** (49 tests / 292 assertions) covering auth, CRUD, authorization, comments, search, rate
  limiting, and the web frontend.
- **Factories + seeder** generating realistic Saudi-themed demo content (Vision 2030, NEOM, AI, tourism,
  heritage, etc.).

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13, PHP 8.5 |
| Database | MySQL (`saudi_blog`) |
| API Auth | Laravel Sanctum |
| Server-rendered views | Blade |
| Interactive UI | React 19 |
| UI components | [Platforms Code UI](https://www.npmjs.com/package/platformscode-new-react) (`platformscode-new-react`) |
| Styling | Tailwind CSS v4 |
| Build tool | Vite |
| Testing | Pest v4 |

## Architecture Notes

This project deliberately keeps Blade as the primary rendering layer and uses React only where
interactivity earns its keep:

- **Blade** renders the home page, post listing shell, single-post pages (SEO-friendly, server-rendered
  content/comments), authentication forms, and the post create/edit/dashboard forms.
- **React** powers exactly one island — `PostsExplorer` on `/posts` — which drives live search, tag
  filtering, and pagination against the public `/api/v1/posts` and `/api/v1/tags` endpoints using real
  Platforms Code components (`DgaSearchBox`, `DgaChip`, `DgaCard`, `DgaPagination`).
- Elsewhere, Platforms Code components are used directly as custom elements in plain Blade markup
  (`<dga-tag>`, `<dga-avatar>`) for presentational, read-only UI — importing any component from the
  package registers all of its custom elements globally, so this works without mounting a React tree.
- `PostController` (web) and `Api\V1\PostController` both delegate to the same `App\Services\PostService`
  and are both gated by the same `App\Policies\PostPolicy` — one implementation, two entry points.

## Installation

```bash
git clone <repo-url>
cd mywebsite

composer install
npm install

cp .env.example .env
php artisan key:generate
```

## Environment

Configure `.env` for your local MySQL instance:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=saudi_blog
DB_USERNAME=root
DB_PASSWORD=
```

Mail (local dev logs mail instead of sending it — see [Queue](#queue)):

```env
MAIL_MAILER=log
QUEUE_CONNECTION=database
```

No real credentials are required for local development. For production, point `MAIL_MAILER` at a real
transport (SMTP/SES/etc.) and set the corresponding `MAIL_*` values.

## Database

```bash
php artisan migrate
php artisan db:seed
```

The seeder creates ~10 authors, 12 topic tags, 30 Saudi-themed posts with attached tags, and comments —
built on the existing `users` / `posts` / `comments` / `tags` / `post_tag` schema (unchanged).

## Storage

Post images are stored on the `public` disk (`storage/app/public`) and served through the public symlink:

```bash
php artisan storage:link
```

## Authentication

Two auth layers, both backed by the same `users` table:

- **Session auth** (Blade): `/login`, `/register`, `/logout` — used by the website itself (dashboard,
  post forms, comments).
- **Token auth** (Sanctum, `/api/v1/*`): register/login returns a bearer token; protected endpoints
  require `Authorization: Bearer <token>`.

## Queue

New-comment notifications are queued (database driver) so the comment request never waits on delivery.
Run a worker locally to process them:

```bash
php artisan queue:work --tries=3
```

The notification declares `$tries = 3` and `$backoff = 30`; if delivery keeps failing it is retried and
then recorded in the `failed_jobs` table (`php artisan queue:failed` to list, `queue:retry` to re-run).

## Comment Notifications

When a user comments on a post, the post's **owner** (`$post->user`) is notified — unless they are the
commenter themselves (no self-notification, and one comment produces exactly one notification; there is
no observer/event duplicating it). The whole thing is one `App\Notifications\NewCommentNotification`
(`implements ShouldQueue`) delivered on two channels:

**1. Database notification (in-site bell)** — `via()` includes `database`, so a row is written to the
`notifications` table (`toArray()` stores the commenter name, post title, comment excerpt, a deep-link
URL, and ids). It appears in the header bell for the owner:

- unread count **badge**,
- dropdown of the latest notifications + **empty state**,
- **Mark as read** (clicking a notification marks it read and opens the comment) and **Mark all as read**,
- a full list page at `/notifications`,
- `read_at` distinguishes read vs. unread.

Routes (auth-only): `GET /notifications`, `POST /notifications/{id}/read`, `POST /notifications/read-all`.

**2. Email notification** — `via()` includes `mail`; `toMail()` renders `resources/views/emails/comment-added.blade.php`,
an Arabic, RTL, brand-styled email (subject **«تعليق جديد على مقالك»**) containing the owner name, article
title, commenter name, comment excerpt, timestamp, and a **«عرض التعليق»** button linking to the comment.

### Queue worker

Both channels are dispatched by the queue worker (see **Queue** above):

```bash
php artisan queue:work --tries=3
```

### Mail settings

Configure the mailer in `.env` (no secrets belong in Git — `.env` is git-ignored):

```env
MAIL_MAILER=log            # local: writes the email to storage/logs/laravel.log
# For real delivery use smtp and set:
# MAIL_MAILER=smtp
# MAIL_HOST=
# MAIL_PORT=
# MAIL_USERNAME=
# MAIL_PASSWORD=
# MAIL_ENCRYPTION=
# MAIL_FROM_ADDRESS=
# MAIL_FROM_NAME="${APP_NAME}"
```

With `MAIL_MAILER=log`, the email is written to `storage/logs/laravel.log` instead of being sent —
convenient for local verification.

### Testing the notifications

```bash
php artisan test --filter=Notification   # bell/database + channels + read/unread
php artisan test --filter=Comment        # comment flow + email content
```

Tests cover: a database notification is created for the correct owner on a new comment, both `database`
and `mail` channels fire once (no duplicate), mark-as-read / mark-all-as-read, read vs. unread counts,
authorization (a user can't read another user's notification), and the email's dynamic content.

## Testing

```bash
php artisan test
```

49 tests / 292 assertions covering registration/login/logout, post CRUD + validation, owner-only
authorization (positive and negative cases), comments (including notification dispatch), search/tag
filtering/pagination, rate limiting on every endpoint, and the Blade web frontend (home, posts,
dashboard, guest redirects, web registration and post creation).

### Continuous Integration

Every push runs the full suite via GitHub Actions (`.github/workflows/tests.yml`) — see the Tests badge
above. The `main` branch requires the "Pest test suite" check to pass before a pull request can merge.

## API Reference

Base URL: `/api/v1`. Authenticated endpoints require `Authorization: Bearer <token>` (obtained from
`/register` or `/login`). Successful responses follow `{"data": ..., "message": "..."}`; paginated list
endpoints return Laravel's standard `{"data": [...], "links": {...}, "meta": {...}}` shape.

### Auth

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/register` | — | Register a user. Body: `name`, `email`, `password`, `password_confirmation`. Returns `201` with `user` + `token`. Rate limited to 5 requests/minute per IP — returns `429` past that. |
| POST | `/login` | — | Body: `email`, `password`. Returns `200` with `user` + `token`, or `422` on invalid credentials. Rate limited to 5 requests/minute per email+IP combination — returns `429` past that. |
| POST | `/logout` | ✓ | Revokes the current access token. Rate limited to 10 requests/minute per user — returns `429` past that. |
| GET | `/user` | ✓ | Returns the authenticated user's profile. |

### Posts

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/posts` | — | Paginated list. Query params: `search` (title/content/tag name), `tag` (slug), `sort` (`latest`\|`oldest`\|`title`), `page`, `per_page`. Shares a 60 requests/minute per-IP rate limit with the single-post endpoint below — returns `429` past that. |
| GET | `/posts/{slug}` | — | Single post with author, tags, and comments. Shares the same 60 requests/minute per-IP rate limit as the list endpoint — returns `429` past that. |
| POST | `/posts` | ✓ | Create. Body (multipart for image): `title`, `content`, `image` (optional file), `published_at` (optional), `tags` (optional array of names). Returns `201`. Rate limited to 5 requests/minute per user — returns `429` past that. |
| PUT/PATCH | `/posts/{slug}` | ✓ (owner) | Update. Same fields, all optional. `403` if not the owner. Shares a 10 requests/minute per-user rate limit with DELETE below — returns `429` past that. |
| DELETE | `/posts/{slug}` | ✓ (owner) | Delete. `403` if not the owner. Shares the same 10 requests/minute per-user rate limit as update — returns `429` past that. |

Example:

```bash
curl "http://localhost:8000/api/v1/posts?search=vision&tag=technology&page=1"
```

### Comments

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/posts/{slug}/comments` | — | Paginated comments for a post. Rate limited to 30 requests/minute per IP — returns `429` past that. |
| POST | `/posts/{slug}/comments` | ✓ | Body: `content`. Queues an email notification to the post owner (skipped if commenting on your own post). Rate limited to 10 requests/minute per user — returns `429` past that. |

### Tags

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/tags` | — | All tags with post counts. Rate limited to 30 requests/minute per IP — returns `429` past that. |
| POST | `/tags` | ✓ | Body: `name` (unique). Rate limited to 10 requests/minute per user — returns `429` past that. |

### Status Codes

`200` OK · `201` Created · `401` Unauthenticated · `403` Forbidden (not the resource owner) · `404` Not
found · `422` Validation error · `429` Too many requests (rate limited).

## Frontend

- **Blade**: `resources/views/layouts/app.blade.php` (RTL, Tajawal), `home.blade.php`, `posts/*`,
  `auth/*`, `dashboard/*` — server-rendered, form-driven, SEO-friendly.
- **React**: `resources/js/components/PostsExplorer.jsx`, mounted from `resources/js/app.js` into
  `#posts-explorer-root` on the posts index page only.
- **Vite**: single entry (`resources/js/app.js`) builds both the plain-JS mount script and the JSX
  island; `@vitejs/plugin-react` handles the JSX transform for `.jsx` files.
- **Tailwind CSS v4**: brand tokens (`--color-brand-*`, `--color-sand-*`, `--color-ink-*`) defined in
  `resources/css/app.css`.
- **Platforms Code UI** (`platformscode-new-react`): real components from the installed package —
  `DgaSearchBox`, `DgaChip`, `DgaCard`, `DgaPagination` in the React island; `DgaTag`, `DgaAvatar` as
  plain custom elements in Blade views. Component names/props were verified against
  `node_modules/platformscode-new-react/dist/types` before use — nothing here is invented.

> **Note on bundle size**: the production JS bundle is ~3.9 MB unminified-equivalent (~630 KB gzipped)
> because `platformscode-new-react`'s barrel export (`components.js`) registers all ~100 of its custom
> elements as a side effect of importing any single one — there's no tree-shaking around that in the
> installed package version. This is a known trade-off of the component library, not something fixed by
> application-level code splitting.

## Running Locally

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
# configure DB_* in .env, then:
php artisan migrate
php artisan db:seed          # optional demo content
php artisan storage:link

npm run build                # or: npm run dev
php artisan serve

# in a separate terminal, to process queued notifications:
php artisan queue:work
```

Visit `http://localhost:8000`.

## Running with Docker

A containerized setup is also available, so the project runs without installing PHP, Composer, or a
local MySQL server.

**Requirements:** Docker Desktop only.

```bash
git clone <repo-url>
cd mywebsite

cp .env.example .env
# set DB_PASSWORD and DB_ROOT_PASSWORD in .env

docker compose up -d --build
```

This builds the app container, starts MySQL (utf8mb4 by default), runs `composer install`, applies
migrations, and starts the server on port 8000.

Seed the primary admin account (required to sign in as an administrator):

```bash
docker compose exec app php artisan db:seed --class=AdminUserSeeder
```

Link storage for uploaded images:

```bash
docker compose exec app php artisan storage:link
```

Visit `http://localhost:8000`.

### Restoring an existing database

To load real content instead of an empty database, import a SQL dump into the running MySQL container:

```bash
docker exec -i mywebsite-db mysql -u root -p"$DB_ROOT_PASSWORD" saudi_blog < saudi_blog.sql
```

If uploaded images are provided separately (`uploads.tar.gz`), extract them before running `storage:link`:

```bash
tar -xzf uploads.tar.gz -C storage/app
```

## License

Licensed under the [MIT License](LICENSE).
