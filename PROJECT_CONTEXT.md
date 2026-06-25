# ThreadForge API

## Project Overview

ThreadForge API is a headless Laravel REST API that helps technology content creators transform raw technical content into optimized X (Twitter) posts using AI.

The platform allows users to:

- Create reusable style configurations (Blueprints)
- Submit raw content such as notes, blog articles, markdown files, or GitHub README content
- Generate structured social media posts through AI
- Manage generated content lifecycle
- Interact with an AI Ghostwriter Assistant
- Use conversation memory and tool calling capabilities

This project is API-only and contains no frontend.

---

# Business Goal

Provide an affordable internal alternative to content repurposing tools such as Taplio and Buffer while maintaining full control over style, prompts, AI workflows, and generated content.

---

# Technical Stack

## Backend

- Laravel 12+
- PHP 8.4+
- MySQL

## Authentication

- Laravel Sanctum

## AI

- laravel/ai
- Structured Output
- Agent Memory
- Tool Calling

## Documentation

- Scribe

## Queue System

- Laravel Queues

## Testing

- PHPUnit or Pest

---

# Architecture Constraints

The following rules are mandatory.

## API Only

The project must be implemented as a pure REST API.

Allowed:

- routes/api.php
- JSON responses
- API Resources

Forbidden:

- Blade
- Vue
- React
- Livewire
- Inertia
- Server-rendered pages

---

## Authentication

Use Laravel Sanctum.

All protected endpoints must use:

auth:sanctum

Unauthorized requests must return:

401 Unauthorized

---

## Validation

All incoming data must be validated through Form Requests.

Validation failures must return:

422 Unprocessable Entity

No controller should contain validation logic.

---

## API Responses

All responses must use Laravel API Resources.

Sensitive fields must never be exposed.

Examples:

- password
- remember_token
- internal AI payloads when not required

---

## Queues

AI generation must be asynchronous.

The API must never wait for the AI response.

Required flow:

Client
→ Submit Raw Content
→ Create Job
→ Return HTTP 202 Accepted
→ Queue processes AI generation
→ Save Generated Post

The request must not block while waiting for the LLM.

---

## Structured Output

The AI response must follow the exact contract below.

{
  "hook_propose": "string",
  "body_points": ["string"],
  "technical_readability_score": 0,
  "suggested_hashtags": ["string"],
  "tone_compliance_justification": "string"
}

Responses that do not respect this structure must be rejected before persistence.

---

## Tool Calling

The AI Assistant must use real Laravel tools.

Required tools:

getCampaignRules(int blueprintId)

Returns blueprint rules.

getPostHistory(int postId)

Returns previous post history.

The assistant must use tools instead of hallucinating data.

---

## Conversation Memory

The AI Assistant must remember previous messages within the same conversation.

Conversation persistence must rely on laravel/ai memory capabilities.

---

# Functional Requirements

## US1 - Authentication

As a creator I can:

- Register
- Login
- Logout

The system returns Bearer Tokens.

---

## US2 - Create Blueprint

As a creator I can create reusable style configurations.

Examples:

- Tone
- Audience
- Character limits
- Hashtag limits
- Emoji policy
- Forbidden words

---

## US3 - List Blueprints

As a creator I can:

- View all blueprints
- View blueprint details

---

## US4 - Submit Raw Content

As a creator I can submit:

- Notes
- Blog content
- Markdown
- GitHub README content

Submission starts the AI generation workflow.

---

## US5 - Generate Structured Post

The system must:

- Process raw content
- Apply blueprint rules
- Generate structured output
- Persist generated post

---

## US6 - Post Lifecycle

Generated posts support:

- draft
- posted
- archived

Users can update publication status.

---

## US7 - Contextual Chat

Users can chat about a generated post.

Example:

"Give me 3 more aggressive hooks"

---

## US8 - Memory

The assistant remembers previous exchanges inside the same conversation.

---

## US9 - Tool Calling

The assistant must access application data through Laravel tools.

---

# Database Model

## users

- id
- name
- email
- password
- timestamps

---

## blueprints

- id
- user_id
- title
- description
- rules (JSON)
- target_audience
- tone
- max_hashtags
- max_caracteres
- allow_emojis
- forbidden_words (JSON)
- regles_supplementaires
- timestamps

---

## raw_contents

- id
- user_id
- blueprint_id
- title
- contenu_brut
- statut
- timestamps

Possible statuses:

- pending
- processing
- completed
- failed

---

## generated_posts

- id
- raw_content_id
- hook_propose
- body_points (JSON)
- technical_readability_score
- suggested_hashtags (JSON)
- tone_compliance_justification
- payload_brut (JSON)
- statut
- posted_at
- timestamps

Possible statuses:

- draft
- posted
- archived

---

## agent_conversations

Managed by laravel/ai.

Optional customization:

generated_post_id

to support contextual conversations.

---

## agent_conversation_messages

Managed by laravel/ai.

---

# Eloquent Cast Requirements

Blueprint:

- rules => array
- forbidden_words => array

GeneratedPost:

- body_points => array
- suggested_hashtags => array
- payload_brut => array

Manual json_encode/json_decode is forbidden.

---

# Required API Endpoints

## Auth

POST /api/register

POST /api/login

POST /api/logout

---

## Blueprints

GET /api/blueprints

POST /api/blueprints

GET /api/blueprints/{id}

PUT /api/blueprints/{id}

DELETE /api/blueprints/{id}

---

## Raw Contents

GET /api/raw-contents

POST /api/raw-contents

GET /api/raw-contents/{id}

---

## Generated Posts

GET /api/generated-posts

GET /api/generated-posts/{id}

PATCH /api/generated-posts/{id}/status

---

## AI Assistant

POST /api/posts/{post}/chat

---

# Performance Requirements

POST /api/raw-contents

must return:

202 Accepted

without waiting for AI completion.

Target response time:

< 100ms

---

# Documentation Requirements

Use Scribe.

Every endpoint must contain:

- Description
- Request Example
- Response Example

Documentation must be generated automatically.

---

# Git Requirements

Minimum:

20 commits

Commit style:

feat(auth): implement sanctum login

fix(posts): validate publication status

refactor(ai): extract generation service

docs(api): add scribe annotations

---

## Retry Strategy

Queue retries for failed generations.

---

# Definition of Done

A feature is considered complete when:

- Migration created
- Model created
- Relationships implemented
- Form Request implemented
- Resource implemented
- Controller implemented
- Routes registered
- Tests written
- Documentation updated

No feature is complete without tests.