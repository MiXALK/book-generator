# SaaS Children Book Generator Roadmap

This roadmap captures the planned development stages for a SaaS product that
generates personalized children's books. A parent enters the child's name, age,
and a goal such as "stop being afraid of the dark" or "learn to share." The
output is a paginated HTML digital storybook where the main character uses the child's name and, for
paid users, can visually resemble the child based on an uploaded photo.

## Product Scope

The service has two main tiers:

- [x] Free users can generate books from a limited set of prepared templates.
- [x] Paid subscribers get an expanded template library and photo-based character
  personalization.

Initial MVP decisions:

- [x] Free tier: 5 free templates and 3 generated books per month.
- [x] Paid tier: all templates, photo personalization, generated illustrations, book
  history, and 10 generated books per month.
- [x] Paid billing: monthly subscription through Stripe; do not use credits in the
  MVP.
- [x] Uploaded child photos require explicit parental consent, must be stored
  privately, and original uploads are deleted after successful generation.

## Technical Baseline

- [x] Backend: Laravel API.
- [x] Frontend: Next.js.
- [x] Database: PostgreSQL.
- [x] Cache and queues: Redis.
- [ ] Queue runtime: Laravel Queue with Redis, with Laravel Horizon for monitoring.
- [x] Local development: Docker Compose.
- [x] Local object storage: MinIO.
- [x] Production object storage: S3-compatible cloud storage.
- [ ] Authentication: Google OAuth.

## Queue Strategy

Use Laravel Queue backed by Redis. Split work by queue name so workers can scale
independently:

- [x] `default` for light background work.
- [ ] `generation-text` for story text generation.
- [ ] `generation-image` for illustration generation.
- [ ] `generation-layout` for paginating book pages.
- [ ] `mail` for email notifications.

## Stage 0: Product Discovery And Constraints

Goal: define product boundaries before implementation.

- [x] Describe user scenarios: free user, paid user, repeat generation, paginated
  in-browser reading, and account deletion.
- [x] Define free limits: available templates, generation quota, and whether free
  books include branding or watermarking.
- [x] Define paid capabilities: expanded templates, photo upload, similar character
  generation, and generation history.
- [x] Define the child photo retention policy, deletion behavior, and parental
  consent flow.
- [x] Confirm the payment provider and subscription model.

Decisions:

- [x] Free users can access 5 free templates and generate 3 books per month.
- [x] Paid subscribers can access all templates, generate 10 books per month, use
  photo personalization, and view book history.
- [x] Billing uses Stripe monthly subscriptions. The MVP does not use credits.
- [x] Uploaded child photos require explicit parental consent. Original uploaded
  photos are deleted after successful generation; generated book files remain
  available until the user deletes them or deletes the account.
- [x] Free books do not define watermarking yet. Add it only if product or cost
  controls require it.

Success criteria: the MVP, tariffs, limits, and media retention policy are
documented.

## Stage 1: Platform Skeleton

Goal: create the application foundation without complex business logic.

- [x] Use a single repository with `backend/` for Laravel and `frontend/` for
  Next.js.
- [x] Add Docker Compose for Laravel, Next.js, PostgreSQL, Redis, and MinIO.
- [x] Add `.env.example` files for backend and frontend without secrets.
- [x] Configure Laravel API, CORS, health endpoint, PostgreSQL, Redis, queue
  connection, and S3-compatible filesystem disk.
- [x] Configure a minimal Next.js app shell with landing page, auth entry points,
  and basic route structure.
- [x] Add initial CI checks for backend tests/lint and frontend lint/typecheck/build.

Success criteria: the project starts locally with one command, the health
endpoint responds, and the frontend can reach the backend API.

## Stage 2: Authentication And Users

Goal: support Google login and a basic account model.

- [x] Implement Google OAuth.
- [x] Create user, plan, and subscription models or provider-backed equivalents.
- [x] Configure authenticated API access between Next.js and Laravel.
- [x] Add middleware for authenticated routes.
- [x] Add a basic account area with profile, subscription status, and generation
  history placeholder.

Success criteria: a user can sign in with Google, Laravel creates or updates the
account, and Next.js can render authenticated state.

## Stage 3: Prompt Catalog And Free Generation

Goal: ship prompt-driven story generation without photo personalization.

- [ ] Keep catalog entities such as `BookTemplate`, `StoryGoal`, and `AgeRange` for
  user-facing selection.
- [ ] Introduce an AI story prompt catalog stored in the database and managed by
  admins.
- [ ] Introduce prompt quality ratings so only high-quality prompts are active for
  generation.
- [ ] Add the generation form: child name, age, goal, and template.
- [ ] Enforce free-tier limits on the backend.
- [ ] Generate story text through AI prompts using child context (name, age, goal)
  with safety and age-appropriateness constraints.
- [ ] Enforce a strict maximum of 80 symbols per compiled page during generation.
- [ ] Persist `BookGeneration` records with statuses such as `draft`, `queued`,
  `processing`, `completed`, and `failed`.

Success criteria: a free user can pick an available template and receive an
interesting AI-generated story adapted to age and goal, with no page exceeding
80 symbols.

## Stage 4: Paginated HTML Reader Pipeline

Goal: create an interactive paginated HTML book reader with strict visual-to-text layout and character limit constraints.

- [ ] Implement backend pagination and text division (splitting story content into
  pages of maximum 80 symbols/characters).
- [ ] Prepare and maintain around 15 HTML layout templates:
  dedicated cover templates, dedicated ending templates, and content templates.
- [ ] Support layout variants with different visual composition and text placement:
  top image, bottom image, left image, right image, and split ratios.
- [ ] Randomize eligible layout templates per page while preserving reproducibility
  in stored page metadata.
- [ ] Render the pages on the Next.js frontend with pagination controls (Next/Prev buttons or swipe gestures).
- [ ] Ensure strict adherence to layout constraints: 80% picture (illustration) space and 20% text container.
- [ ] Store generated illustrations and metadata in S3 or MinIO through Laravel Filesystem.
- [ ] Add transition animations and a beautiful reading mode interface on the frontend.
- [ ] Add a generation status page that navigates directly to the HTML book reader when complete.

Success criteria: after submitting the form, the user sees progress and can read
their beautifully laid out, paginated book directly in the browser with varied
cover/content/ending layouts and strict 80/20 + 80-symbol constraints.

## Stage 5: Subscriptions And Access Limits

Goal: monetize the expanded product surface.

- [ ] Integrate Stripe Checkout or the selected payment provider.
- [ ] Handle subscription webhooks.
- [ ] Restrict paid templates to active subscribers.
- [ ] Enforce generation quotas for free and paid plans.
- [ ] Add subscription management UI.
- [ ] Prevent duplicate or excessive generation requests above plan limits.

Success criteria: free users see paid templates as locked, and paid users can use
the expanded catalog.

## Stage 6: Photo Personalization And Illustrations

Goal: add the key paid feature.

- [ ] Allow photo upload only for paid users.
- [ ] Validate image size, MIME type, dimensions, and parental consent.
- [ ] Store original uploads under private S3 prefixes.
- [ ] Run illustration generation through the `generation-image` queue.
- [ ] Model relationships between `ChildProfile`, `UploadedPhoto`,
  `GeneratedCharacter`, and `BookGeneration`.
- [ ] Decide whether a generated character is reused per child profile or generated
  per book.
- [ ] Provide retry and clear failure states for image generation.

Success criteria: a paid user uploads a photo and receives a book with
illustrations where the character resembles the child.

## Stage 7: Security, Privacy, And Compliance

Goal: reduce risk when processing child-related data.

- [ ] Minimize collected data: child name, age, goal, and photo only when needed.
- [ ] Require explicit parental consent before photo processing.
- [ ] Support account deletion and deletion of photos and generated books.
- [ ] Keep S3 objects private and use short-lived signed URLs.
- [ ] Avoid logging personal data or private file URLs.
- [ ] Add retention cleanup for temporary files and failed generations.
- [ ] Protect upload endpoints with MIME validation, file size limits, and rate
  limits.

Success criteria: users can delete their data, private files are not directly
public, and logs avoid personally identifiable information.

## Stage 8: Observability And Operations

Goal: make generation failures and bottlenecks visible.

- [ ] Add Laravel Horizon for queue monitoring.
- [ ] Add structured logs and a correlation ID per book generation.
- [ ] Track latency for text generation, image generation, and paginated HTML assembly.
- [ ] Add retry and backoff policies for external AI or rendering services.
- [ ] Monitor failed jobs.
- [ ] Add email or in-app notifications when a book is ready.

Success criteria: operators can see where a generation stalled and why jobs are
failing.

## Stage 9: Admin And Content Operations

Goal: manage templates without code changes.

- [ ] Add an admin role.
- [ ] Add CRUD for goals, age groups, templates, scenes, and free/paid availability.
- [ ] Add CRUD for AI story prompts and prompt rating metadata.
- [ ] Add template preview.
- [ ] Add layout-template management for cover/content/ending variants.
- [ ] Version templates so old generations remain reproducible.
- [ ] Add moderation or manual review before publishing new templates.
- [ ] Add prompt quality workflows: scoring, activation thresholds, and publication
  controls.

Success criteria: a content manager can manage prompts, ratings, and layout
templates, then publish high-quality story generation configurations without a
developer changing code.

## Stage 10: Scaling And Cost Optimization

Goal: prepare the product for growth after demand is validated.

- [ ] Split worker pools by queue and resource requirements.
- [ ] Cache the template catalog in Redis.
- [ ] Store generation results and avoid regenerating unchanged paginated HTML storybook layouts.
- [ ] Add idempotency keys for generation requests.
- [ ] Add quotas and throttling for expensive AI operations.
- [ ] Track per-book cost: text, images, layout assembly, storage, and bandwidth.

Success criteria: generation cost is measurable, queues scale independently, and
duplicate requests do not create duplicate expensive work.

## MVP Boundary

The first release should include:

- [x] Google login.
- [x] 5 free templates and 3 generated books per month for free users.
- [ ] Form fields for child name, age, goal, and template.
- [ ] AI prompt-based story generation without photo personalization.
- [ ] Asynchronous book layout generation and pagination.
- [ ] Illustration storage in MinIO or S3.
- [ ] User account area with book history.
- [ ] Generation statuses and monthly limit enforcement.

The first release should not include:

- [x] Photo personalization.
- [x] Complex admin panel if template seeding is enough.
- [x] Multiple payment providers.
- [x] Deep character customization.
- [x] Native mobile applications.

## Main Risks

- [x] Processing photos of children requires strict privacy, deletion, consent, and
  logging rules.
- [x] Photo-based character generation can be expensive and unreliable, so it should
  follow paid validation.
- [x] Computationally expensive image/illustration generation needs separate worker resources.
- [x] Free-tier limits must exist early to control generation costs.
- [x] Content must remain safe for children, especially if free-form AI text
  generation is introduced later.
