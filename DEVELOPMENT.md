# Silvernoise — Developer Reference

This document is the authoritative reference for everyone (and every AI session) working on the Silvernoise backend. Read it before adding any new feature, endpoint, model, or integration. Keep it updated whenever a decision changes.

---

## Table of Contents

1. [Architecture](#1-architecture)
2. [Stack & Why](#2-stack--why)
3. [Directory Layout](#3-directory-layout)
4. [Database Conventions](#4-database-conventions)
5. [API Conventions](#5-api-conventions)
6. [Authentication & Authorization](#6-authentication--authorization)
7. [Role-Based Access Control](#7-role-based-access-control)
8. [Status Enums](#8-status-enums)
9. [KVZ Music API Integration](#9-kvz-music-api-integration)
10. [File Storage (Cloudflare R2)](#10-file-storage-cloudflare-r2)
11. [Payment Logic](#11-payment-logic)
12. [Email & SMTP](#12-email--smtp)
13. [Queue & Jobs](#13-queue--jobs)
14. [Security Rules](#14-security-rules)
15. [Code Patterns](#15-code-patterns)
16. [Adding New Features — Checklist](#16-adding-new-features--checklist)
17. [Things That Must Never Happen](#17-things-that-must-never-happen)

---

## 1. Architecture

```
silvernoise.bg          → Nginx → public/  (static HTML — preview.html)
seller.silvernoise.bg   → Nginx → public/  (static HTML — seller-central.html)
admin.silvernoise.bg    → Nginx → public/  (static HTML — admin-central.html, internal only)
api.silvernoise.bg      → Nginx → Laravel  (all /api/* routes)
```

- The frontend is **static HTML** served directly by Nginx. It speaks only to `/api/*`.
- Laravel is **API-only** — no Blade templates, no web routes (except the activation email link).
- Admin Central is accessed at a **hidden/internal URL**. It must never appear in public navigation.
- All three frontends share one Laravel API, separated by route middleware groups.

---

## 2. Stack & Why

| Layer | Choice | Reason |
|---|---|---|
| Language | PHP 8.2 | Mature ecosystem, Laravel fit, team familiarity |
| Framework | Laravel 11 | First-party queue, auth, storage, HTTP client |
| Database | PostgreSQL 16 | JSONB for `kvz_raw`, reliable decimal math for earnings |
| Cache / Queue | Redis | Fast queue driver for KVZ sync and newsletter jobs |
| File Storage | Cloudflare R2 | S3-compatible, **zero egress fees** — critical for large audio files |
| Auth | Laravel Sanctum | Token-based API auth, supports SPA and mobile |
| Provisioning | Laravel Forge + Hetzner CX21 | Zero-config server management; Hetzner needed for large file uploads (Vercel has 4.5 MB body limit) |

**Do not switch to Vercel or serverless** — audio file uploads require a persistent server without body-size limits.

---

## 3. Directory Layout

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/           # AuthController only
│   │   │   ├── Seller/         # DashboardController, ReleaseController,
│   │   │   │                   # ReportController, PaymentController
│   │   │   └── Admin/          # CustomerController, LabelController,
│   │   │                       # ReleaseController, TrackController,
│   │   │                       # ReportController, PaymentController,
│   │   │                       # EmailTemplateController, NewsletterController,
│   │   │                       # SmtpController, DashboardController,
│   │   │                       # ApiLogController, EmailLogController,
│   │   │                       # ReferenceController
│   │   ├── Requests/
│   │   │   ├── Auth/
│   │   │   ├── Seller/
│   │   │   └── Admin/
│   │   └── Resources/          # API response transformers
│   ├── Jobs/                   # SyncKvzReleases, SendNewsletterCampaign
│   └── Models/                 # One file per table
├── database/
│   ├── migrations/             # 21 files, numbered 000001–000021
│   └── seeders/                # DatabaseSeeder only
├── routes/
│   └── api.php                 # All routes — no web.php routes except activation
├── .env.example                # Template — never commit real .env
└── DEVELOPMENT.md              # This file
```

---

## 4. Database Conventions

### Naming
- Tables: `snake_case`, plural (`release_artists`, `payment_requests`)
- Columns: `snake_case`
- Foreign keys: `{model}_id` (e.g., `label_id`, `customer_id`)
- Pivot tables: `{table_a}_{table_b}` in alphabetical order (`release_artists`, `track_genres`)
- Timestamps: always include `created_at` / `updated_at` via `$table->timestamps()`

### Migration numbering
Migrations are ordered `2024_01_01_000001` through `2024_01_01_000021`. New migrations use the next available number. Do not renumber existing ones.

### Key table decisions

**`users`** — stores sellers, admins, and finance staff. No `iban`, `paypal_email`, or `wise_email`. Payment details are captured per payment request, not per user.

**`releases`** — has `kvz_id` (VARCHAR UNIQUE, extracted from KVZ cover_art URL), `kvz_synced_at`, and `kvz_raw` (JSONB — full raw response stored for auditability). Internal `catalog_id` maps to KVZ `catalogid`.

**`release_artists` / `track_artists`** — pivot tables with three extra columns:
- `role` VARCHAR(100) DEFAULT `'Performer'` — KVZ roles: Performer, Composer, Lyricist, Remixer, Featured
- `is_primary` BOOLEAN DEFAULT `false` — maps from KVZ `primary: 1/0`
- `sort_order` SMALLINT DEFAULT `0` — display order from KVZ

**`payment_requests`** — IBAN-only payments. Columns: `iban`, `bank_name` (nullable), `account_holder` (nullable). No PayPal, no Wise, no JSONB payment_details blob.

**`reports`** — `status` can be: `unpaid`, `payment_requested`, `paid`. Only `unpaid` reports can have a payment request created against them.

**`smtp_settings`** — password stored encrypted via Laravel `Crypt::encryptString()`. Never store plaintext SMTP passwords.

### PostgreSQL-specific features in use
- `JSONB` for `kvz_raw` (releases table) — use `->jsonb('kvz_raw')` in migrations
- `TO_CHAR(period_start, 'Mon YYYY')` for dashboard trend grouping — PostgreSQL-only syntax, do not abstract to a DB-agnostic form
- Decimal columns use `decimal(12, 4)` for earnings — 4 decimal places for streaming micro-payments

---

## 5. API Conventions

### URL structure
```
POST   /api/auth/login
GET    /api/auth/me
GET    /api/seller/dashboard
GET    /api/seller/releases
GET    /api/admin/customers
POST   /api/admin/customers
```

- Prefix: `/api/`
- Groups: `auth/`, `seller/`, `admin/`
- Resources: plural nouns (`releases`, `customers`, `reports`)
- Actions that aren't CRUD: verb suffix (`/activate`, `/block`, `/reset-password`, `/upload`, `/sync`)

### Response format
All responses are JSON. Use Laravel API Resources for all model responses — never return Eloquent models directly.

**Success (single resource):**
```json
{ "data": { ... } }
```

**Success (paginated collection):**
```json
{
  "data": [...],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "last_page": 5, "per_page": 20, "total": 94 }
}
```

**Error:**
```json
{ "message": "Human-readable error." }
```

**Validation error (422):**
```json
{ "message": "...", "errors": { "field": ["message"] } }
```

### HTTP status codes
| Situation | Code |
|---|---|
| GET success | 200 |
| POST created | 201 |
| Action success (no body needed) | 200 with `{ "message": "..." }` |
| Unauthenticated | 401 |
| Forbidden (wrong role / blocked) | 403 |
| Not found | 404 |
| Validation failed | 422 |
| Server error | 500 |

### Pagination
Default: `->paginate(20)`. Use `->simplePaginate()` only when total count is expensive and not needed by the UI.

---

## 6. Authentication & Authorization

**Driver:** Laravel Sanctum — token-based. Every authenticated request carries `Authorization: Bearer {token}`.

**Login checks (in order):**
1. Email + password match → 401 if not
2. `is_active = true` → 403 if false
3. `is_blocked = false` → 403 if true
4. `contract_terminated_at IS NULL` → 403 if set

**Activation flow:**
1. Admin creates customer → `is_active = false`, `activation_token` set (random 64-char string)
2. Email sent with link: `GET /api/auth/activate/{token}`
3. On success: `is_active = true`, `activation_token = null`, `email_verified_at = now()`

**Password reset:**
- Uses Laravel's built-in `Password::sendResetLink()` / `Password::reset()`
- `forgotPassword()` always returns 200 regardless of whether the email exists (prevents email enumeration)
- On successful reset: all existing tokens deleted (`$user->tokens()->delete()`)

**Middleware:**
- `auth:sanctum` — validates Bearer token
- `seller.active` — requires `role = seller`, `is_active = true`, `is_blocked = false`
- `admin.role` — requires `role IN (admin, finance)`

---

## 7. Role-Based Access Control

| Role | Access |
|---|---|
| `seller` | `/api/seller/*` only. Sees only their own labels, releases, reports, payments. |
| `admin` | `/api/admin/*` full access. Can manage all customers, releases, reports, payments, email. |
| `finance` | `/api/admin/*` — same middleware as admin. Restrict sensitive operations (block/terminate) in controller logic if needed. |

**Seller data isolation** — every seller query must scope to `customer_id = auth()->id()`. Never return another seller's data. Example:
```php
Report::where('customer_id', $request->user()->id)->findOrFail($id);
```

**Featured flag** — `users.featured = true` marks clients whose labels/releases appear on the public website. Set/unset via `POST /api/admin/customers/{id}/feature`.

---

## 8. Status Enums

### Release status
Internal enum values (stored in DB):

| Value | Meaning |
|---|---|
| `draft` | Created internally, not yet submitted |
| `pending` | Submitted to KVZ, awaiting approval |
| `approved` | Approved by KVZ |
| `delivered` | Delivered to stores |
| `live` | Live on stores |
| `takedown` | Taken down / archived |

**KVZ → internal mapping** (see `SyncKvzReleases::mapStatus()`):

| KVZ status | Internal status | Notes |
|---|---|---|
| `pending` | `pending` | Direct map |
| `approved` | `approved` | Direct map |
| `delivered` | `delivered` | Direct map |
| `media_pool` | `delivered` | Pooled = delivered but not yet live |
| `taken_down` | `takedown` | |
| `for_takedown` | `takedown` | Takedown requested |
| `archive` | `takedown` | Archived = effectively taken down |
| `unknown` | `draft` | Default fallback |

### Report status

| Value | Meaning |
|---|---|
| `unpaid` | Report uploaded, no payment request yet |
| `payment_requested` | Seller submitted payment request |
| `paid` | Admin marked as paid |

Only `unpaid` reports can receive a new `PaymentRequest`. A report transitions to `payment_requested` when the payment request is created, and to `paid` when the admin sets payment status to `completed`.

### Payment request status

| Value | Meaning |
|---|---|
| `pending` | Just submitted by seller |
| `processing` | Admin is processing |
| `sent` | Payment sent, awaiting confirmation |
| `completed` | Confirmed paid — triggers report → `paid` |
| `rejected` | Rejected (admin adds note) |

---

## 9. KVZ Music API Integration

### Base URL and auth
```
Base URL:  https://api.kvzmusic.com/rest
Auth:      X-KVZ-APIKey: {value from KVZ_API_KEY env var}
```

**Security non-negotiables:**
- The API key lives ONLY in `.env` as `KVZ_API_KEY`. Never in frontend code, Git, or logs.
- All KVZ calls are made server-side (Laravel). The frontend never calls KVZ directly.
- Support key rotation: changing `KVZ_API_KEY` in `.env` must be the only required step.
- Use HTTPS for all KVZ requests (enforced by the base URL).

### Endpoint used
```
GET /rest/releases?page={n}
```
Returns 100 releases per page. `total_pages` in the response envelope tells you how many pages exist (~22,458 releases → ~225 pages).

### Response envelope
```json
{
  "status": "ok",
  "message": "...",
  "page": 1,
  "per_page": 100,
  "total_pages": 225,
  "count": 100,
  "total": 22458,
  "releases": [...]
}
```

### KVZ field names — exact names from OpenAPI spec

**Release object:**
| KVZ field | Internal column | Notes |
|---|---|---|
| `cover_art` | `cover_art_url` | URL to authenticated endpoint — requires API key to fetch |
| `upc` | `upc` | Primary sync anchor when kvz_id unavailable |
| `catalogid` | `catalog_id` | Note: no underscore in KVZ name |
| `title` | `title` | |
| `title_version` | `title_version` | |
| `label` | `label.name` | String → resolve/create Label |
| `copyright_c_line` | `copyright_c` | Note: `_line` suffix in KVZ name |
| `copyright_p_line` | `copyright_p` | Note: `_line` suffix in KVZ name |
| `status` | `status` | Map via `mapStatus()` |
| `genres` | `genres` (pivot) | Array of strings |
| `artists` | `release_artists` (pivot) | Array of artist objects |
| `tracks` | `tracks` | Array of track objects |

**Artist object (inside release or track):**
| KVZ field | Internal field | Notes |
|---|---|---|
| `artist_name` | `artists.name` | Note: NOT `name` |
| `role` | `release_artists.role` | Performer, Composer, Lyricist, Remixer, Featured |
| `primary` | `release_artists.is_primary` | Integer 1/0 → boolean |

**Track object:**
| KVZ field | Internal column | Notes |
|---|---|---|
| `isrc` | `isrc` | Primary upsert anchor for tracks |
| `volume_number` | `disc_number` | KVZ calls disc "volume" |
| `track_number` | `track_number` | |
| `title` | `title` | |
| `title_version` | `title_version` | |
| `language` | `audio_language` | Full name string ("English", "Bulgarian") |
| `explicit_lyrics` | `explicit_lyrics` | Integer 0/1 → boolean |
| `genres` | `track_genres` (pivot) | Array of strings |
| `artists` | `track_artists` (pivot) | Same artist structure as release |

**Fields that do NOT exist in KVZ response (do not map):**
- `id` — no explicit ID field; extract from `cover_art` URL instead
- `release_date` — not in KVZ response
- `original_release_date` — not in KVZ response

### Extracting kvz_id
```php
preg_match('/\/releases\/(\d+)\/cover_art/', $raw['cover_art'], $matches);
$kvzId = $matches[1] ?? null;
```

### Upsert anchor
- Prefer `kvz_id` if available
- Fall back to `upc` if `kvz_id` could not be extracted

### Cover art
The `cover_art` field is a URL that requires the API key as a header — it is not a public image URL. Store it in `cover_art_url` as-is. To display cover art:
- Option A: Proxy through a Laravel route that adds the `X-KVZ-APIKey` header
- Option B: Download and cache to R2 during sync, store cached path in `cover_art_path`
  
`cover_art_path` column exists on releases for cached/uploaded images. `cover_art_url` is the KVZ source URL.

### Sync job behaviour
- Class: `App\Jobs\SyncKvzReleases`
- Queue: `redis` driver
- Tries: 3, Timeout: 3600s (1 hour)
- Rate limiting: 200ms sleep between pages (`usleep(200000)`)
- Every page request is logged to `api_logs` table (source: `kvz`)
- On failure: logs error + creates `api_logs` entry, does not crash the whole sync
- `customer_id` defaults to `1` (admin) for KVZ-imported releases — assign to real customer via Admin Central label assignment

### Triggering sync
```
POST /api/admin/kvz/sync
```
Only accessible to admin role. Dispatches `SyncKvzReleases::dispatch($userId)` onto the queue.

---

## 10. File Storage (Cloudflare R2)

**Driver:** `r2` — configured as an S3-compatible disk in `config/filesystems.php`.

**Why R2 over S3:** Zero egress fees. Critical for audio file playback previews and PDF report downloads.

### File paths by type
| File type | Path pattern |
|---|---|
| Audio files | `audio/{release_id}/{track_id}_{filename}` |
| Report PDFs | `reports/{label_id}/{year}/{filename}` |
| Invoices | `invoices/{customer_id}/{report_id}_{filename}` |
| Cover art cache | `covers/{kvz_id}.jpg` |

### Signed URLs
- Report downloads: `Storage::disk('r2')->temporaryUrl($path, now()->addMinutes(15))`
- Invoices visible to seller: `now()->addDays(30)`
- Never serve R2 files through a public permanent URL unless explicitly public

### Audio file upload endpoint
```
POST /api/admin/tracks/{id}/audio
```
Accepts multipart form. Stores to R2, updates `tracks.audio_file_path` and `tracks.audio_file_size`.

---

## 11. Payment Logic

### Rules (must be enforced in code)
1. Only reports with `status = 'unpaid'` can receive a payment request. Validate with `findOrFail` scoped to `customer_id` AND `status = 'unpaid'`.
2. A seller may only create a payment request for their own reports (`customer_id = auth()->id()`).
3. Payment amount is taken from `report.total_earnings` — never from seller input.
4. Currency is taken from `report.currency` — never from seller input.
5. IBAN is provided by the seller at payment-request time — NOT stored on the user profile.
6. When a payment request is created, the report status immediately moves to `payment_requested`.
7. When admin sets payment request status to `completed`, the report status moves to `paid` and `paid_at` is set.

### IBAN handling
- Stored on `payment_requests.iban` (max 50 chars)
- `bank_name` and `account_holder` are optional helpers
- IBAN is not validated for format (international variations) — stored as-is
- Never store IBAN on the `users` table

### Invoice upload
- Optional PDF, max 10 MB
- Stored to R2 at `invoices/{customer_id}/{report_id}_{filename}`
- `invoice_url` is a 30-day signed URL — regenerate on demand if expired

---

## 12. Email & SMTP

### SMTP settings
Stored in `smtp_settings` table (single row). Password is encrypted with `Crypt::encryptString()` via model accessor/mutator. Never log or expose the decrypted password.

### Email templates
Stored in `email_templates` table with keys:
- `password_reset`
- `payment_notification`
- `release_approved`
- `quarterly_report`

Templates are bilingual (BG/EN): `subject_bg`, `subject_en`, `body_bg`, `body_en`. Variables use `{{variable_name}}` syntax. Render via `EmailTemplate::render(string $lang, array $variables)`.

### Newsletter
- Campaigns stored in `newsletter_campaigns`
- Segments: `all`, `active`, `inactive`, `featured` (targets `users.featured = true`)
- Language field per campaign — determines which template language to use
- Sending dispatched as `SendNewsletterCampaign` job (queued, never synchronous)
- Every send logged to `email_logs` (recipient, template, status, sent_at)

---

## 13. Queue & Jobs

**Driver:** Redis. Configure in `.env`: `QUEUE_CONNECTION=redis`.

### Running queues on the server
```bash
php artisan queue:work redis --queue=default --tries=3 --timeout=3600
```
Managed by Supervisor (configured via Laravel Forge).

### Jobs
| Job | Queue | Description |
|---|---|---|
| `SyncKvzReleases` | default | Full KVZ sync — paginated, 225+ pages |
| `SendNewsletterCampaign` | default | Sends campaign emails to segmented users |

### Adding a new job
1. `php artisan make:job MyJob`
2. Implement `handle()` and `failed()`
3. Set `$tries` and `$timeout` explicitly
4. Log to `api_logs` or `email_logs` as appropriate
5. Dispatch from a controller, never from another job (avoid nesting)

---

## 14. Security Rules

These are non-negotiable. Do not override them for convenience.

### API key security
- `KVZ_API_KEY` must only ever exist in `.env` (server-side)
- Never pass it to the frontend, never log it, never include it in API responses
- Access via `config('services.kvz.api_key')` — never `env()` in code outside config files
- Rotation procedure: update `.env`, restart PHP-FPM. No code changes needed.

### Admin Central
- Must be accessed via a **hidden/internal URL** — never linked from the public website or seller central
- Route prefix: `/api/admin/*` — protected by `admin.role` middleware
- The static HTML file (`admin-central.html`) is served by Nginx but not referenced in any public navigation

### Authentication
- Passwords hashed with `Hash::make()` (bcrypt, Laravel default)
- Tokens created via Sanctum (`createToken()->plainTextToken`)
- On password reset: all tokens deleted
- Login failures: generic "Invalid credentials" message — never reveal whether the email exists

### Data isolation
- Every seller query MUST be scoped by `customer_id = auth()->id()`
- Never use user-supplied IDs without ownership validation

### File uploads
- Audio files: validated mime type, stored to R2, never executed
- Invoices: PDFs only (`mimes:pdf`), max 10 MB
- Never store uploaded files on the local disk in production

---

## 15. Code Patterns

### Model
- All fillable fields declared in `$fillable` (use allowlist, not `$guarded = []`)
- Casts declared in `casts()` method (Laravel 11 style)
- Relationships as typed return methods (`BelongsTo`, `HasMany`, `BelongsToMany`)
- Pivot relationships use `withPivot(...)` and `orderByPivot(...)`
- Accessors follow `get{Name}Attribute()` naming
- No business logic in models beyond simple helpers (`isPaid()`, `isLive()`, etc.)

### Controller
- One controller per resource per area (Admin/Seller)
- Inject `FormRequest` for all create/update operations
- Return `JsonResponse` with typed return hint
- Scope all queries immediately — no lazy scoping
- Use `findOrFail()` — never `find()` followed by a manual null check
- Use `response()->json(new XyzResource($model))` — never `$model->toArray()`

### Form Request
- One request class per create/update operation
- `authorize()` returns `true` — authorization is handled by middleware
- Unique rules: always exclude current record on update: `'unique:table,col,' . $this->route('id')`

### API Resource
- All resources extend `JsonResource`
- Use `$this->whenLoaded('relation', fn() => ...)` for related data
- Always include `is_primary` on artist pivot mappings (both releases and tracks)
- Format dates with `->format('Y-m-d')` or `->format('Y-m-d H:i:s')`
- Cast decimals to float: `(float) $this->amount`
- Cast booleans explicitly: `(bool) $a->pivot->is_primary`

### Migration
- Run `$table->timestamps()` on every table
- Foreign keys use `->constrained('table')->cascadeOnDelete()` for owned data, `->restrictOnDelete()` for shared data (e.g., artists)
- Index foreign keys immediately after definition: `$table->index('release_id')`
- Use `->jsonb()` for PostgreSQL JSONB columns — not `->json()`

---

## 16. Adding New Features — Checklist

Before shipping any new feature, verify:

- [ ] New table? → Migration created with correct naming, timestamps, indexes, FK constraints
- [ ] New model? → `$fillable`, `casts()`, relationships, added to relevant parent model
- [ ] New endpoint? → Route in correct group (`auth/`, `seller/`, `admin/`), correct middleware
- [ ] New controller method? → FormRequest for input, Resource for output, ownership scoped
- [ ] Touches seller data? → Scoped to `customer_id = auth()->id()`
- [ ] Touches KVZ? → Uses correct field names from spec (see §9), API key from config only
- [ ] File upload? → Stored to R2, validated mime/size, path saved to DB
- [ ] Email? → Uses EmailTemplate with bilingual BG/EN, variables documented
- [ ] Long-running? → Dispatched as a queued Job, not synchronous
- [ ] New status value? → Added to migration enum AND documented in §8
- [ ] Sensitive data? → Encrypted at rest (see SmtpSetting pattern)
- [ ] `database-schema.md` updated?
- [ ] This document (`DEVELOPMENT.md`) updated if behaviour changes?

---

## 17. Things That Must Never Happen

| ❌ Never do this | ✅ Do this instead |
|---|---|
| Expose `KVZ_API_KEY` in frontend or API response | Keep in `.env`, access via `config('services.kvz.api_key')` |
| Call KVZ API from frontend JavaScript | Proxy through a Laravel controller |
| Use `$raw['id']` from KVZ response | Extract from `cover_art` URL via regex |
| Use `$raw['catalog_id']` | Use `$raw['catalogid']` (no underscore) |
| Use `$raw['copyright_c']` / `$raw['copyright_p']` | Use `$raw['copyright_c_line']` / `$raw['copyright_p_line']` |
| Use `$artistData['name']` | Use `$artistData['artist_name']` |
| Store IBAN on the `users` table | Store on `payment_requests.iban` at request time |
| Store PayPal email or Wise email | IBAN only — no other payment methods |
| Let sellers create payment requests for paid reports | Validate `status = 'unpaid'` in controller |
| Return raw Eloquent models from controllers | Use API Resources |
| Store SMTP password in plaintext | Use `Crypt::encryptString()` via model mutator |
| Link to Admin Central from public nav | Admin Central is internal-only — hidden URL |
| Upload files to local disk in production | Use R2 (`Storage::disk('r2')`) |
| Use `env()` in application code | Use `config()` — `env()` only in `config/*.php` files |
| Serve audio files through Laravel | Use R2 signed URLs directly |
| Skip `is_primary` on artist pivot | Always map `primary` (KVZ) → `is_primary` (bool) in both release and track artists |
| Switch to Vercel or serverless | Hetzner VPS required — Vercel has 4.5 MB body limit |
