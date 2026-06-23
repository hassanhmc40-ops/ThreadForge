# ThreadForge API — AGENTS.md

## Project Overview

ThreadForge API is a **headless REST API** built with **Laravel 13.x** that enables tech content creators to automatically transform raw technical notes, blog posts, or GitHub READMEs into posts optimized for X (Twitter). The application separates **style** (reusable Blueprints) from **content**, uses an **AI agent** (Laravel/AI SDK) for generation, and processes everything **asynchronously via queues**.

**Architecture:** Pure REST API — no Blade views, no Inertia, no Livewire. All responses are standardised JSON via Laravel API Resources.

**Database:** SQLite (`DB_CONNECTION=sqlite`), queue driver is `database`.

---

## Current State (Baseline)

| Component | Status |
|---|---|
| Laravel version | 13.x (`laravel/framework: ^13.8`) |
| API route file (`routes/api.php`) | ❌ Missing (must be created) |
| Sanctum | ❌ Not installed |
| Laravel/AI SDK | ❌ Not installed |
| Scribe | ❌ Not installed |
| Custom Models | ❌ Only default `User` |
| Custom Migrations | ❌ Only default users/cache/jobs tables |
| Custom Controllers | ❌ None |
| Form Requests | ❌ None |
| API Resources | ❌ None |
| Jobs | ❌ None |
| Tools (Agent) | ❌ None |
| `.env` | ✅ Configured (SQLite, database queue) |
| `bootstrap/app.php` | ✅ Has `api/*` JSON exception handling, but no `api` route registered yet |

---

## Tech Stack

- **Backend:** Laravel 13.x (PHP 8.3+)
- **Auth:** Laravel Sanctum (Bearer Token)
- **AI:** `laravel/ai` SDK (OpenAI-compatible — Grok API)
- **Queue:** Database driver
- **Cache:** Database driver
- **Docs:** `knuckleswtf/scribe`
- **Database:** SQLite (local), MySQL (production-ready schema in migrations)
- **Testing:** PHPUnit 12.x

---

## Data Model (MCD → MLD)

### Entity Relationship

```
User (1) ──<creates>── (N) Blueprint
User (1) ──<submits>── (N) RawContent
Blueprint (1) ──<structures>── (N) RawContent
RawContent (1) ──<generates>── (0..1) GeneratedPost
GeneratedPost (1) ──<initiates>── (0..1) AgentConversation
AgentConversation (1) ──<contains>── (N) AgentConversationMessage
```

### Tables Schema

#### `users` (default Laravel migration)
- `id` — PK
- `name` — VARCHAR
- `email` — VARCHAR (unique)
- `password` — VARCHAR (hashed)
- `timestamps`

#### `blueprints`
| Column | Type | Notes |
|---|---|---|
| `id` | PK | auto-increment |
| `user_id` | FK → users.id | constrained, cascade |
| `name` | VARCHAR | e.g. "Tech Twitter Style" |
| `tone` | VARCHAR | e.g. "professional yet relaxed" |
| `max_hashtags` | INT | default 1 |
| `max_characters` | INT | default 280 |
| `regles_supplementaires` | TEXT | nullable, JSON string of extra rules |
| `timestamps` | | |

#### `raw_contents`
| Column | Type | Notes |
|---|---|---|
| `id` | PK | auto-increment |
| `user_id` | FK → users.id | direct ownership check |
| `blueprint_id` | FK → blueprints.id | which style rules to apply |
| `contenu_brut` | TEXT | raw markdown/text submitted |
| `statut` | VARCHAR | default 'en_attente' (pending/processing/completed/failed) |
| `timestamps` | | |

#### `generated_posts`
| Column | Type | Notes |
|---|---|---|
| `id` | PK | auto-increment |
| `raw_content_id` | FK → raw_contents.id | unique (one-to-one) |
| `hook_propose` | VARCHAR(280) | AI-generated hook |
| `body_points` | JSON | cast to native `array` |
| `technical_readability_score` | INT | 0–100 |
| `suggested_hashtags` | JSON | cast to native `array` |
| `tone_compliance_justification` | TEXT | |
| `payload_brut` | JSON | nullable, full raw AI response |
| `statut` | VARCHAR | default 'draft' (draft/archived/posted) |
| `timestamps` | | |

#### `agent_conversations` (managed by SDK + custom FK)
| Column | Type | Notes |
|---|---|---|
| `id` | PK | |
| `generated_post_id` | FK → generated_posts.id | **CUSTOM** — we add this column |
| `session_id` | VARCHAR | SDK internal |
| `timestamps` | | |

#### `agent_conversation_messages` (managed by SDK)
| Column | Type | Notes |
|---|---|---|
| `id` | PK | |
| `conversation_id` | FK → agent_conversations.id | |
| `role` | VARCHAR | 'user' or 'assistant' |
| `content` | TEXT | |
| `timestamps` | | |

---

## API Endpoints

| Method | URL | Auth | Controller | Purpose |
|---|---|---|---|---|
| POST | `/api/register` | ❌ | AuthController | Register new user |
| POST | `/api/login` | ❌ | AuthController | Login, get Bearer token |
| POST | `/api/logout` | ✅ | AuthController | Revoke token |
| GET | `/api/user` | ✅ | UserController | Current user info |
| POST | `/api/blueprints` | ✅ | BlueprintController | Create blueprint |
| GET | `/api/blueprints` | ✅ | BlueprintController | List user's blueprints (+ posts count) |
| GET | `/api/blueprints/{id}` | ✅ | BlueprintController | Single blueprint details |
| PUT | `/api/blueprints/{id}` | ✅ | BlueprintController | Update blueprint |
| DELETE | `/api/blueprints/{id}` | ✅ | BlueprintController | Delete blueprint |
| POST | `/api/content/repurpose` | ✅ | ContentController | Submit raw content (returns 202) |
| GET | `/api/posts` | ✅ | PostController | List generated posts (filterable by status) |
| GET | `/api/posts/{id}` | ✅ | PostController | Single post details |
| PATCH | `/api/posts/{id}/status` | ✅ | PostController | Update post status (draft/archived/posted) |
| POST | `/api/posts/{id}/chat` | ✅ | ChatController | Start/continue conversation |
| GET | `/api/posts/{id}/chat` | ✅ | ChatController | Get conversation history |

---

## Implementation Plan (Build Order)

### Phase 1: Foundation & Auth (US1)
1. Install Sanctum (`composer require laravel/sanctum`)
2. Publish Sanctum config & migration, run migrations
3. Create `routes/api.php` with `api.php` routing in `bootstrap/app.php`
4. Create `AuthController` — `register()`, `login()`, `logout()`
5. Create `RegisterRequest`, `LoginRequest` Form Requests (422 JSON errors)
6. Create `UserResource` API Resource (hide password, dates, internal keys)
7. Configure `auth:sanctum` middleware on protected routes
8. Test: All auth flows return clean JSON, 401 on invalid token

### Phase 2: Blueprints (US2-US3)
1. Create `Blueprint` model with `$fillable`, `$casts`, `user()` relationship
2. Create `blueprints` migration
3. Create `BlueprintController` — CRUD, with eager loading (`with('generatedPosts')` for count)
4. Create `StoreBlueprintRequest`, `UpdateBlueprintRequest` Form Requests
5. Create `BlueprintResource` API Resource
6. Test: CRUD operations, validation errors, N+1 prevention

### Phase 3: Async Content Processing (US4-US6)
1. Install `laravel/ai` SDK (`composer require laravel/ai`)
2. Configure Grok API in `config/services.php` + `.env`
3. Create `RawContent` model with `user()`, `blueprint()`, `generatedPost()` relationships
4. Create `raw_contents` migration
5. Create `GeneratedPost` model with `rawContent()` relationship, casts for JSON columns
6. Create `generated_posts` migration
7. Create `ProcessContentJob` (dispatched to queue)
   - Calls Grok API via `laravel/ai` with structured output schema
   - Validates response has exact keys: `hook_propose`, `body_points`, `technical_readability_score`, `suggested_hashtags`, `tone_compliance_justification`
   - Creates `GeneratedPost` record
   - Updates `RawContent.statut` from `en_attente` → `processing` → `completed` / `failed`
8. Create `ContentController` — `repurpose()` returns 202 immediately
9. Create `RepurposeRequest` Form Request
10. Create `PostController` — `index()`, `show()`, `updateStatus()`
11. Create `PostResource` API Resource (JSON columns as native arrays, no manual encode/decode)
12. Create `UpdatePostStatusRequest` Form Request
13. Run migrations
14. Test: 202 response under 100ms, job processes, structured output enforced

### Phase 4: Ghostwriter Agent (US7-US9)
1. Add `generated_post_id` FK column to `agent_conversations` via custom migration
2. Create `GetCampaignRules` Tool class (Laravel/AI Tool):
   ```php
   // Takes int $campaignId, queries blueprints table, returns style rules
   ```
3. Create `GetPostHistory` Tool class:
   ```php
   // Takes int $postId, queries generated_posts table, returns previous versions
   ```
4. Create `ChatController`:
   - `start()` — creates/maintains conversation for a post, uses agent with tools + memory
   - `history()` — returns message history
5. Configure agent with:
   - Conversation memory (SDK's built-in tables)
   - Tool calling for `GetCampaignRules` and `GetPostHistory`
6. Test: Agent returns real DB data (no hallucinations), memory persists across chat turns

### Phase 5: Documentation & Polish
1. Install Scribe (`composer require knuckleswtf/scribe --dev`)
2. Add comprehensive PHPDoc to all controllers
3. Configure Scribe for API docs
4. Run `php artisan scribe:generate`
5. Review all Resources for data leaks (no password, no raw timestamps, no internal keys)
6. Verify eager loading everywhere (zero N+1)
7. Test all endpoints with invalid tokens (401)
8. Test all endpoints with invalid data (422)

---

## Critical Technical Constraints

### JSON API Resources (No Data Leaks)
- **Never** expose: `password`, `remember_token`, raw `created_at`/`updated_at` (format them), internal IDs if not needed
- Use `API Resource` classes for every response
- Return only what the frontend needs

### Form Requests (No SQL Errors)
- Every POST/PUT/PATCH endpoint uses a dedicated Form Request
- Errors return `422 Unprocessable Entity` with field-specific messages in JSON
- Never let raw SQL errors reach the client

### Sanctum Auth
- All routes except `register`/`login` use `auth:sanctum`
- Invalid/missing token → `401 Unauthorized`
- Custom 401 response format in `bootstrap/app.php` exception handler

### Async Queue (202 Accepted)
- `POST /api/content/repurpose` dispatches a job and returns `202` immediately
- Response time must be < 100ms
- Job status tracked in `raw_contents.statut`

### Eloquent Casts (No Manual JSON)
- `body_points` and `suggested_hashtags` are JSON columns with `$casts = ['body_points' => 'array', 'suggested_hashtags' => 'array']`
- **No** `json_encode()` or `json_decode()` anywhere in controllers

### Structured Output (AI Contract)
- The Grok API response must be validated against the exact schema before DB insertion
- Job fails gracefully if schema doesn't match

### N+1 Prevention
- Use `->with()` (eager loading) for all relationships
- Example: `Blueprint::with('generatedPosts')->where('user_id', $userId)->get()`

### Git Hygiene
- Minimum 20 commits, format: `feat(auth): ...`, `feat(blueprints): ...`, `fix(content): ...`, `refactor(resource): ...`
- No massive "Project finished" commits
- Atomic commits per feature

---

## Key Files & Directories (After Build)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── BlueprintController.php
│   │   ├── ContentController.php
│   │   ├── PostController.php
│   │   └── ChatController.php
│   ├── Requests/
│   │   ├── RegisterRequest.php
│   │   ├── LoginRequest.php
│   │   ├── StoreBlueprintRequest.php
│   │   ├── UpdateBlueprintRequest.php
│   │   ├── RepurposeRequest.php
│   │   └── UpdatePostStatusRequest.php
│   └── Resources/
│       ├── UserResource.php
│       ├── BlueprintResource.php
│       ├── PostResource.php
│       └── ChatMessageResource.php
├── Jobs/
│   └── ProcessContentJob.php
├── Models/
│   ├── User.php
│   ├── Blueprint.php
│   ├── RawContent.php
│   └── GeneratedPost.php
└── Tools/
    ├── GetCampaignRules.php
    └── GetPostHistory.php
database/
├── migrations/
│   ├── xxxx_create_blueprints_table.php
│   ├── xxxx_create_raw_contents_table.php
│   ├── xxxx_create_generated_posts_table.php
│   └── xxxx_add_generated_post_id_to_conversations.php
routes/
├── api.php
└── web.php
config/
├── sanctum.php
└── scribe.php
```

---

## Commands Reference

```bash
# Setup
composer install
php artisan key:generate
cp .env.example .env

# Sanctum
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate

# Laravel/AI SDK
composer require laravel/ai

# Scribe
composer require knuckleswtf/scribe --dev
php artisan vendor:publish --tag=scribe-config
php artisan scribe:generate

# Queue
php artisan queue:table
php artisan migrate
php artisan queue:listen --tries=3

# Dev (from composer.json)
npm run dev
```

---

## Conversation Memory Architecture

The Ghostwriter feature uses the `laravel/ai` SDK's built-in conversation tables (`agent_conversations`, `agent_conversation_messages`). We add a custom `generated_post_id` FK to `agent_conversations` to link each chat session to a specific generated post. This enables:

1. **Q1:** "Translate the post to English" → agent translates using post content
2. **Q2:** "Give me another hook for this one" → agent knows "this one" = the translated post from Q1, because conversation history is persisted in the SDK tables

The agent uses **real PHP Tools** (`GetCampaignRules`, `GetPostHistory`) that query the database, guaranteeing zero hallucinations about blueprint rules or post data.

---

## Verification Checklist

- [ ] Auth: register, login return token; logout revokes; invalid token → 401
- [ ] Blueprints: CRUD works, posts count included, validation returns 422
- [ ] Content: POST returns 202 < 100ms; job processes async
- [ ] Posts: list, show, status update; JSON columns are native arrays
- [ ] Chat: agent answers using real DB tools; conversation history persists
- [ ] No data leaks: passwords, raw timestamps, internal IDs not in JSON
- [ ] Zero N+1 queries: all relationships eager loaded
- [ ] Scribe docs: complete with request/response examples
- [ ] Git: 20+ atomic commits with conventional commit messages
- [ ] All endpoints return proper status codes (200, 201, 202, 401, 422, 404)
