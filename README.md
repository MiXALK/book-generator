# Book Generator

Book Generator is a SaaS project for generating personalized children's books.
Parents provide a child's name, age, and developmental goal; the service returns
a personalized paginated HTML digital storybook. Paid subscribers will also be able to upload a child
photo so generated illustrations can use a similar-looking character.

## Project Documentation

- `@AGENTS.md` describes how AI coding agents should work in this repository.
- `@CONVENTIONS.md` is the authoritative source for project conventions,
  structure, constraints, and design decisions.
- `@ROADMAP.md` captures the product roadmap, MVP boundary, delivery stages, and
  major implementation risks.

## Development

See `@CONVENTIONS.md` for the selected stack and repository structure.

Copy environment examples before starting local services, then fill in private
values in the copied files (never commit `.env` or `.env.local`):

```sh
cp backend/.env.example backend/.env
cp .env.example .env
```

Example files list variable names only. For local Docker Compose, service
defaults in `docker-compose.yml` apply when a variable is unset or empty.

Install backend dependencies through Docker:

```sh
docker compose build backend
docker compose run --rm --no-deps backend composer install
```

Run database migrations through Docker:

```sh
docker compose exec backend php artisan migrate
```

Seed the initial catalog data (goals, templates, prompts, layouts):

```sh
docker compose exec backend php artisan db:seed
```

Run the local stack:

```sh
docker compose up --build
```

The Laravel API is served by FrankenPHP from `backend/public`.

Service URLs:

- Frontend: [http://localhost:3000](http://localhost:3000)
- Backend API: [http://localhost:8000/api](http://localhost:8000/api)
- Backend health check: [http://localhost:8000/api/health](http://localhost:8000/api/health)
- Horizon dashboard: [http://localhost:8000/horizon](http://localhost:8000/horizon)
- MinIO console: [http://localhost:9001](http://localhost:9001)

## Admin panel

The content admin UI lives in the Next.js frontend. Access is granted by email,
not by a separate login.

1. Add your Google account email to `ADMIN_EMAILS` in `backend/.env` (comma-separated
   for multiple admins):

   ```sh
   ADMIN_EMAILS=you@example.com,teammate@example.com
   ```

2. Restart the backend so the new value is loaded:

   ```sh
   docker compose restart backend
   ```

3. Sign in at [http://localhost:3000](http://localhost:3000) with Google using
   one of those emails. On each Google OAuth login the backend promotes matching
   accounts to the `admin` role automatically.

4. Open the admin panel directly or from the **Content admin** link on the
   [dashboard](http://localhost:3000/dashboard) (visible only when `role` is
   `admin`).

Admin URLs (frontend):

- Dashboard and review queue: [http://localhost:3000/admin](http://localhost:3000/admin)
- Goals: [http://localhost:3000/admin/goals](http://localhost:3000/admin/goals)
- Templates: [http://localhost:3000/admin/templates](http://localhost:3000/admin/templates)
- Prompts: [http://localhost:3000/admin/prompts](http://localhost:3000/admin/prompts)

Non-admin users are redirected away from `/admin/*`. Admin API routes under
`/api/admin/*` require the same role and return `403` otherwise.

Validation commands:

```sh
docker compose run --rm --no-deps backend ./vendor/bin/pint --test
docker compose run --rm --no-deps backend composer phpstan
docker compose run --rm --no-deps backend php artisan test
docker compose run --rm --no-deps frontend npm run lint
docker compose run --rm --no-deps frontend npm run build
```

## Contributing

Keep changes small, intentional, and aligned with `@CONVENTIONS.md`. If a change
introduces or modifies a project convention, update `@CONVENTIONS.md` in the same
change.
