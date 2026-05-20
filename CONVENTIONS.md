# Project Conventions

`CONVENTIONS.md` serves the same purpose as `project-specs.md`: it is the
authoritative source for project conventions, folder structure, technology
choices, and design decisions.

## Current State

The project roadmap defines the target stack and delivery sequence:

- Backend: Laravel API.
- Frontend: Next.js.
- Database: PostgreSQL.
- Cache and queues: Redis.
- Queue runtime: Laravel Queue with Redis, monitored by Laravel Horizon.
- Local development: Docker Compose.
- Local object storage: MinIO.
- Production object storage: S3-compatible cloud storage.
- Authentication: Google OAuth.

Runtime commands, package managers, test commands, and source layout are not
implemented yet. Add them deliberately when introducing the application
skeleton.

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

## PHP Rules

- Write PHP code according to PSR-12.

## Documentation Rules

- Update this file when adding or changing conventions, dependencies, folder
  structure, tooling, or architectural decisions.
- Update `ROADMAP.md` when changing planned product stages, MVP scope, delivery
  order, or product risks.
- Keep documentation concise and specific.
- Prefer durable rules over tool-specific instructions.
- Keep AI-agent workflow guidance in `AGENTS.md`.

## Repository Structure

```text
.
├── AGENTS.md
├── CONVENTIONS.md
├── ROADMAP.md
├── README.md
└── .gitignore
```

Update this tree when the project gains source code, tests, scripts, or
additional documentation.

## Validation

No validation commands are defined yet. When tooling is introduced, add the
canonical commands to `README.md` and keep this section focused on validation
expectations.
