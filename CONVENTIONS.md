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
that will not grow over time. Age bands are modeled this way (`AgeRange`), and
child gender must use fixed `boy` / `girl` values.

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

## AI Image Generation

Illustration generation uses provider abstractions:

- Contract: `backend/app/Services/Ai/Contracts/IllustrationGenerationProviderInterface.php`
- Default driver: `yandexart` (Yandex Cloud Foundation Models YandexART async REST API)
- HTTP transport: `backend/app/Services/Ai/Providers/YandexArtIllustrationProvider.php`
- Alternate driver: `openai` via `OpenAiCompatibleIllustrationProvider` (OpenAI-compatible `/images/generations` API)
- Driver resolution: `backend/app/Services/Ai/IllustrationGenerationProviderFactory.php`
- Prompt composition: `IllustrationPromptComposer` (page fragment + shared style bible) and `CharacterBibleComposer`
- Queue: `GenerateBookIllustrationsJob` on the `generation-image` queue
- Configure via `AI_IMAGE_DRIVER`, `AI_IMAGE_API_KEY`, and `AI_IMAGE_FOLDER_ID` in `.env`. Driver `base_url`, `model`, `timeout`, `operations_url`, `poll_interval_seconds`, and `aspect_ratio` (YandexART) or `size` (OpenAI) live in `config/services.php` under `ai_image.drivers`.
- YandexART auth uses `Authorization: Api-Key <key>`; the service account API key needs `yc.ai.imageGeneration.execute`.
- When the image provider is not configured, book generation keeps SVG placeholder illustrations and still deletes uploaded photos after successful generation.

Photo upload validation limits are defined in `config/services.php` under `book_photo`.

Character rules:

- Free-tier and no-upload generation paths use default boy/girl character
  presets selected from child gender, not user-provided media.
- Paid uploads are character-basis inputs only. They generate or refresh a
  reusable personalized character for the whole book, not a per-page image
  reference.
- Character reuse: one `GeneratedCharacter` per `ChildProfile` (unique per
  `user_id` + `child_name`). New uploads refresh the linked photo reference but
  keep the existing style bible for visual consistency across books.

## Privacy And Data Deletion

Child-related data is minimized to generation inputs (name, age, goal) and optional paid-tier photos:

- Service: `backend/app/Services/UserDataDeletionService.php` (book/account deletion, S3 purge).
- Account deletion: `DELETE /api/user` with `{ "confirm": true }`; cancels active Stripe subscriptions when configured.
- Book deletion: `DELETE /api/books/{id}` removes DB records and S3 assets for the generation.
- Signed illustration URLs expire after `config('services.privacy.signed_url_ttl_minutes')` (default 60 minutes).
- Retention cleanup: `php artisan privacy:purge-expired` (scheduled daily) deletes pending photos older than 24 hours and failed generations older than 7 days.
- Parental consent is required at upload and re-checked before illustration processing.
- Logs avoid child PII and private storage paths.

## Observability And Operations

Queue monitoring uses Laravel Horizon (`php artisan horizon`). The dashboard is
available at `/horizon` in local development.

- Service: `BookGenerationObservabilityService` (correlation IDs, structured stage
  logs, per-stage latency recording, book-ready notifications).
- Each `BookGeneration` stores `correlation_id`, `text_duration_ms`,
  `layout_duration_ms`, and `image_duration_ms`.
- Structured JSON logs use the `structured` log channel (`php://stderr`) for
  container-friendly output; set `LOG_STACK=structured` in Docker.
- Illustration jobs (`GenerateBookIllustrationsJob`) retry transient AI/storage
  failures with configurable exponential backoff
  (`services.observability.job_backoff_seconds`).
- Failed queue jobs are logged through `LogFailedQueueJob`; Horizon surfaces
  failed jobs in the dashboard. `horizon:snapshot` and `queue:prune-failed` run
  on the scheduler.
- Book-ready email: `BookReadyNotification` on the `mail` queue when generation
  completes. Frontend status polling remains the in-app notification path.
- Configure via `OBSERVABILITY_NOTIFY_ON_BOOK_READY` and
  `OBSERVABILITY_BOOK_READER_URL` in `.env`.

## Admin And Content Operations

Content managers manage catalog entities without code changes or re-seeding:

- Admin role: `users.role` enum (`user`, `admin`). Promoted automatically on Google
  login when the email is listed in `ADMIN_EMAILS` (comma-separated in `.env`).
- Middleware: `admin` (`EnsureAdmin`) on `/api/admin/*` routes (requires `auth.api`).
- Admin API: CRUD for `StoryGoal`, `BookTemplate`, `StoryPrompt`, and
  `LayoutTemplate`; publication workflow (`draft` → `pending_review` → `published`);
  template preview endpoints; review queue.
- Publication status: `publication_status` on catalog tables. Consumer catalog and
  generation paths only use `published` + `is_active` records.
- Versioning: `version` column plus `*_versions` snapshot tables
  (`book_template_versions`, `story_prompt_versions`, `layout_template_versions`).
  Snapshots are written on publish. `book_generations.book_template_snapshot` stores
  template metadata at generation time (alongside existing `prompt_snapshot`).
- Prompt quality: `StoryPromptRating` submissions via admin API; scores aggregated
  into `quality_score` / `rating_count`. Publication requires minimum thresholds in
  `config/services.php` under `content`.
- Frontend admin UI: `/admin` (goals, templates, prompts, layouts, preview, review
  queue). Visible to users with `role: admin`.

## Scaling And Cost Optimization

Generation runs as a queued pipeline so worker pools can scale independently:

- Jobs: `GenerateBookTextJob` (`generation-text`), `AssembleBookLayoutJob`
  (`generation-layout`), `GenerateBookIllustrationsJob` (`generation-image`),
  `BookReadyNotification` (`mail`).
- Horizon supervisors are split per queue with distinct memory and timeout settings
  in `config/horizon.php`.
- Template catalog reads are cached in Redis via `TemplateCatalogCacheService`;
  cache version bumps on admin publish (`ContentPublicationService`).
- Layout assembly is cached by story hash + catalog version (`BookLayoutCacheService`);
  `book_generations.story_text` stores generated text for the pipeline.
- Idempotency: clients send `Idempotency-Key`; replays return the existing
  generation without duplicate work (`BookGenerationIdempotencyService`).
- AI quotas: daily text/image limits via `AiOperationQuotaService`; illustration
  retries throttled (`books-retry-illustrations`).
- Cost tracking: `BookGenerationCostService` records `cost_breakdown` and
  `total_cost_usd` on `book_generations` (text tokens, images, layout, storage,
  bandwidth). Configure rates under `config/services.php` (`scaling`, `cost`).

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
