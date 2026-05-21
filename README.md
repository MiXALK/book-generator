# Book Generator

Book Generator is a SaaS project for generating personalized children's books.
Parents provide a child's name, age, and developmental goal; the service returns
a personalized PDF story. Paid subscribers will also be able to upload a child
photo so generated illustrations can use a similar-looking character.

## Project Documentation

- `@AGENTS.md` describes how AI coding agents should work in this repository.
- `@CONVENTIONS.md` is the authoritative source for project conventions,
  structure, constraints, and design decisions.
- `@ROADMAP.md` captures the product roadmap, MVP boundary, delivery stages, and
  major implementation risks.

## Development

See `@CONVENTIONS.md` for the selected stack and repository structure.

Copy environment examples before starting local services:

```sh
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env.local
```

Install backend dependencies through Docker:

```sh
docker compose build backend
docker compose run --rm --no-deps backend composer install
```

Run the local stack:

```sh
docker compose up --build
```

The Laravel API is served by FrankenPHP from `backend/public`.

Service URLs:

- Frontend: `http://localhost:3000`
- Backend API: `http://localhost:8000/api`
- Backend health check: `http://localhost:8000/api/health`
- MinIO console: `http://localhost:9001`

Validation commands:

```sh
docker compose run --rm --no-deps backend ./vendor/bin/pint --test
docker compose run --rm --no-deps backend php artisan test
docker compose run --rm --no-deps frontend npm run lint
docker compose run --rm --no-deps frontend npm run build
```

## Contributing

Keep changes small, intentional, and aligned with `@CONVENTIONS.md`. If a change
introduces or modifies a project convention, update `@CONVENTIONS.md` in the same
change.
