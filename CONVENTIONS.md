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
- Prefer shared helpers or existing patterns over duplicated functionality.
- Ensure generated code compiles cleanly and passes available validation checks.

## Book Design & Layout Decisions

To maximize children's engagement and ensure readability, we adhere to a paginated digital format instead of standard downloadable PDFs:

- **No PDF Generation**: Books are delivered entirely as interactive, paginated HTML/CSS experiences rendered on the Next.js frontend.
- **Page Layout Ratio**: Each page layout must strictly allocate **80% of its visual space to the picture/illustration**, and **20% to the text container**.
- **Character Constraint**: The text content on any single page must be strictly limited to a maximum of **80 characters (symbols)**, including letters, spaces, and punctuation, to ensure early developmental focus.

## PHP Rules

- Write PHP code according to PSR-12.

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
