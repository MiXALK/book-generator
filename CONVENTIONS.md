# Project Conventions

This file is the authoritative source for project agreements, folder structure,
technologies, and design decisions. Keep it concise and up to date.

## Current State

The project roadmap defines the target stack and delivery sequence:

- Backend: Laravel API.
- Frontend: Next.js.
- Database: PostgreSQL.
- Cache and queues: Redis.
- Queue runtime: Laravel Queue with Redis, monitored by Laravel Horizon.
- Local development: Docker Compose. Backend PHP and Composer commands run in
  the backend Docker image, not on the host machine.
- Backend web runtime: Alpine-based FrankenPHP on PHP 8.5 with required Laravel
  extensions installed.
- Backend CLI workloads such as Composer, Artisan, tests, and queue workers run
  in the same backend Docker image.
- Local object storage: MinIO.
- Production object storage: S3-compatible cloud storage.
- Authentication: Google OAuth.

Runtime commands, package managers, test commands, and source layout are
documented in `@README.md`.

## Source Layout

- `backend/` contains the Laravel API.
- `frontend/` contains the Next.js application.
- `docker-compose.yml` defines the local development services.
- `.github/workflows/ci.yml` defines initial backend and frontend validation.

## General Rules

- Ask for clarification when requirements or feature scope are unclear.
- Keep diffs minimal and focused on the requested change.
- Do not add scaffold, demo, placeholder, or unrelated example code.
- Do not introduce dependencies or change build/configuration files without
  explicit approval.
- Follow existing naming, indentation, file organization, linting, and formatting
  conventions once they exist.
- Preserve existing comments. Add new comments only for non-obvious logic.
- Do not leave commented-out environment variables, configuration keys, or dead
  code in the repository. Optional settings belong in `config/` defaults (or
  driver presets documented there); list only active keys in `.env.example`.
- `.env.example` files are key catalogs only: no secrets, credentials, API keys,
  database names, usernames, passwords, or other private or environment-specific
  values. Leave those empty; developers set real values in local `.env` files
  (gitignored). Local Docker defaults for unset variables live in
  `docker-compose.yml`, not in committed examples.
- Prefer shared helpers or existing patterns over duplicated functionality.
- Ensure generated code compiles cleanly and passes available validation checks.

## Book Design & Layout Decisions

To maximize children's engagement and ensure readability, we adhere to a paginated digital format instead of standard downloadable PDFs:

- **No PDF Generation**: Books are delivered entirely as interactive, paginated HTML/CSS experiences rendered on the Next.js frontend.
- **Page Layout Ratio**: Each page layout must strictly allocate **80% of its visual space to the picture/illustration**, and **20% to the text container**.
- **Character Constraint**: The LLM generates one continuous story; the backend paginates it into pages with a **soft target of 80 characters (symbols)** per page, including letters, spaces, and punctuation. Sentences are packed greedily up to that limit; a hard cap still applies so no page exceeds 80 symbols.

## Localization & Language Decisions

To support multiple nationalities while maintaining local compliance, the application is built with native multi-language capability:

- **Supported Languages**: **Russian (RU)** is established as the primary/main application language, with **English (EN)** provided as the additional supported language.
- **Backend Sync**: A `language` string preference is persisted on the `users` table and updated via the protected PUT `/api/user/language` API route.
- **Frontend Localization**: Translations are managed through lightweight client-side dictionaries (`frontend/src/app/context/locales.ts`) and dynamically synchronized with the user profile database via the unified `AuthContext`.

## Fixed Domain Constants

Use backed PHP enums in `backend/app/Enums/` for small, fixed product constants
that will not grow over time. Age bands are modeled this way (`AgeRange`).

Growable catalog entities (for example, `StoryGoal`, `BookTemplate`, and
`StoryPrompt`) stay in the database and use repositories. UI labels for enum
values are resolved on the frontend through `frontend/src/app/context/locales.ts`.

## PHP Rules

- Write PHP code according to PSR-12.
- Run static analysis with PHPStan and Larastan (`composer phpstan` in `backend/`).
- Larastan infers Eloquent model properties from `database/migrations`; add explicit
  `BelongsTo` / `HasMany` return types on relationship methods so relation checks pass.
- Do not call `env()` outside `config/` files; read values via `config()` in application code.
- Prefer `readonly class` for DTOs and services that only hold immutable constructor
  state and do not need subclassing.
- Prefer readable code over dense one-liners. Do not combine multiple function
  calls, casts, or array operations on a single line when splitting them into
  named intermediate variables would make the logic easier to follow. One clear
  step per line is preferred over chaining two or three language constructs
  together.

## Database Access (Repository Pattern)

All persistence and database queries must go through repository abstractions. Do not
call Eloquent query builders or model `create`/`update` methods directly from
controllers, middleware, or services.

- Define contracts in `backend/app/Repositories/Contracts/` (for example,
  `UserRepositoryInterface`).
- Provide Eloquent implementations in `backend/app/Repositories/Eloquent/` (for
  example, `EloquentUserRepository`).
- Bind each contract to its implementation in `AppServiceProvider`.
- Inject repository interfaces into controllers, services, and middleware.
- Keep Eloquent models focused on relationships, casts, and fillable attributes;
  query logic belongs in repositories.

When adding a new entity or query path, add or extend a repository contract first,
then use it from the application layer.

For validated API input, use Form Request classes in `backend/app/Http/Requests/`
with typed accessor methods (for example, `bookTemplateId(): int`) instead of
casting values in controllers.

## AI Text Generation

Story text generation must use provider abstractions, not a hard-coded vendor:

- Contract: `backend/app/Services/Ai/Contracts/StoryTextGenerationProviderInterface.php`
- Default driver: `qwen` (DashScope OpenAI-compatible chat completions API)
- HTTP transport: `backend/app/Services/Ai/Providers/OpenAiCompatibleStoryTextProvider.php`
- Driver resolution: `backend/app/Services/Ai/StoryTextGenerationProviderFactory.php`
- Configure via `AI_TEXT_DRIVER` and `AI_TEXT_API_KEY` in `.env`. `AI_TEXT_API_KEY`
  is the DashScope API key for the Qwen driver. Each driver's `base_url`, `model`,
  `timeout`, and optional `request` extras are defined only in
  `config/services.php` under `ai_text.drivers`.
- Alternate driver: `deepseek` remains available when that API is reachable.
- Optional per-driver `request` array is merged into the chat completions POST
  body (for example, `response_format` for structured JSON output).

## Billing And Subscriptions

Stripe monthly subscriptions gate paid templates and higher generation quotas:

- Service: `backend/app/Services/StripeBillingService.php` (Checkout, Customer Portal, webhooks).
- Access rules: `backend/app/Services/SubscriptionAccessService.php` (`free`: 3 books/month, `paid` + `active`: 10 books/month and paid templates).
- Configure via `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_PRICE_ID`, and redirect URLs in `.env`. Driver defaults live in `config/services.php` under `stripe`.
- Webhook endpoint: `POST /api/webhooks/stripe` (Stripe-Signature verification required).

## Documentation Rules

- Update this file when adding or changing conventions, dependencies, folder
  structure, tooling, or architectural decisions.
- Update `@ROADMAP.md` when changing planned product stages, MVP scope, delivery
  order, or product risks.
- Keep documentation concise and specific.
- Prefer durable rules over tool-specific instructions.
- Keep AI-agent workflow guidance in `@AGENTS.md`.

## Repository Structure

```text
.
├── AGENTS.md
├── CONVENTIONS.md
├── ROADMAP.md
├── README.md
├── backend/
│   └── app/
│       └── Repositories/
│           ├── Contracts/
│           └── Eloquent/
├── docker-compose.yml
├── frontend/
├── .github/
└── .gitignore
```

Update this tree when the project gains source code, tests, scripts, or
additional documentation.

## Validation

Use the canonical validation commands in `@README.md`. Backend PHP must satisfy
PSR-12 through Laravel Pint, and frontend code must pass linting, type checking,
and production build checks.
