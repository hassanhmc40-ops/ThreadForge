# ThreadForge API

A **headless REST API** built with **Laravel 13.x** that transforms raw technical content (notes, blog posts, markdown, GitHub READMEs) into structured X/Twitter posts using AI.

## Features

- **Authentication** — Register, login, logout with Sanctum Bearer tokens
- **Blueprints** — Reusable style configurations (tone, hashtag limits, character limits, extra rules)
- **AI Content Generation** — Async queue processing via Groq API with structured output enforcement
- **Post Lifecycle** — Draft, posted, archived status management
- **Ghostwriter Assistant** — Conversational AI agent with tool calling and memory across chat turns
- **Tool Calling** — Real database queries via `GetCampaignRules` and `GetPostHistory`
- **API Documentation** — Auto-generated via Scribe

## Tech Stack

| Component | Technology |
|---|---|
| Framework | Laravel 13.x (PHP 8.4+) |
| Auth | Laravel Sanctum (Bearer Token) |
| AI SDK | `laravel/ai` (Groq provider) |
| Queue | Database driver |
| Docs | `knuckleswtf/scribe` |
| Database | MySQL (production), SQLite (testing) |
| Testing | PHPUnit 12.x |

## Architecture Diagram

![Diagram](public/images/Diagramme.png)

## Getting Started

### Requirements

- PHP 8.4+
- Composer
- MySQL
- Groq API key

### Installation

```bash
git clone <repo-url>
cd ThreadForge
composer install
cp .env.example .env
php artisan key:generate
```

Configure your `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=threadforge
DB_USERNAME=root
DB_PASSWORD=

GROQ_API_KEY=your-groq-api-key
GROQ_MODEL=meta-llama/llama-4-scout-17b-16e-instruct
AI_DEFAULT=groq
```

Run migrations and start the queue:

```bash
php artisan migrate
php artisan queue:listen --tries=3
```

### Development Server

```bash
php artisan serve
```

## API Endpoints

| Method | URL | Auth | Description |
|---|---|---|---|
| POST | `/api/register` | ❌ | Register a new user |
| POST | `/api/login` | ❌ | Login, get Bearer token |
| POST | `/api/logout` | ✅ | Revoke current token |
| GET | `/api/user` | ✅ | Current user profile |
| GET | `/api/blueprints` | ✅ | List user's blueprints |
| POST | `/api/blueprints` | ✅ | Create a blueprint |
| GET | `/api/blueprints/{id}` | ✅ | Get blueprint details |
| PUT | `/api/blueprints/{id}` | ✅ | Update blueprint |
| DELETE | `/api/blueprints/{id}` | ✅ | Delete blueprint |
| GET | `/api/content` | ✅ | List raw contents |
| POST | `/api/content/repurpose` | ✅ | Submit raw content for AI processing (202) |
| GET | `/api/content/{id}` | ✅ | Get raw content details |
| GET | `/api/posts` | ✅ | List generated posts |
| GET | `/api/posts/{id}` | ✅ | Get post details |
| PATCH | `/api/posts/{id}/status` | ✅ | Update post status |
| POST | `/api/posts/{id}/chat` | ✅ | Chat with Ghostwriter assistant |
| GET | `/api/posts/{id}/chat` | ✅ | Get chat history |

## Authentication

All protected endpoints require a Bearer token in the `Authorization` header:

```
Authorization: Bearer 1|abc123...
```

Get a token via `POST /api/register` or `POST /api/login`. Tokens are revoked via `POST /api/logout`.

Invalid or missing tokens return:

```json
{ "message": "Unauthenticated." }
```

## Status Codes

| Code | Description |
|---|---|
| 200 | Success |
| 201 | Created |
| 202 | Accepted (async processing) |
| 204 | No content (deletion) |
| 401 | Unauthenticated |
| 404 | Resource not found |
| 422 | Validation error |

## Testing

```bash
php artisan test
```

Tests use an in-memory SQLite database with `RefreshDatabase`. 44 feature tests cover all endpoints:

- AuthTest — register, login, logout, user, 401
- BlueprintTest — CRUD, ownership scoping, validation
- ContentTest — repurpose (202), listing, filtering
- PostTest — lifecycle, JSON column assertions
- ChatTest — validation, 404, empty history

## API Documentation

Generate Scribe documentation:

```bash
php artisan scribe:generate
```

Then access at `http://localhost/docs`.
