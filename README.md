# wildlife-tracking-backend

## Project Overview

wildlife-tracking-backend is a Laravel 11 API-First / Headless backend built to serve the Next.js frontend of the Wildlife Tracking Monitoring System. It exposes RESTful APIs with strict validation, resource-based serialization, and token-based authentication via Laravel Sanctum.

## Tech Stack

- Laravel 11
- PHP 8.3+
- Laravel Sanctum 4
- Eloquent ORM
- API Resources (JsonResource)
- Pest PHP / PHPUnit
- Redis
- Vite / Tailwind (build tooling)

## MySQL Database Setup

1. Create a MySQL database for the project.
2. Update `.env` with your MySQL credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wildlife_tracking
DB_USERNAME=your_username
DB_PASSWORD=your_password
```
3. Run migrations and seeders:
```bash
php artisan migrate --seed
```

## Detailed Folder Structure

```text
wildlife-tracking-backend/
+-- app/
¦   +-- Http/
¦   ¦   +-- Controllers/
¦   ¦   ¦   +-- Api/
¦   ¦   ¦   +-- Controller.php
¦   ¦   +-- Resources/
¦   +-- Models/
¦   +-- Providers/
+-- bootstrap/
+-- config/
+-- database/
¦   +-- factories/
¦   +-- migrations/
¦   +-- seeders/
+-- public/
+-- resources/
+-- routes/
¦   +-- api.php
¦   +-- console.php
¦   +-- web.php
+-- storage/
+-- tests/
+-- vendor/
```

Key paths:

- `app/Http/Controllers/` - Application controllers. API controllers live under `app/Http/Controllers/Api/`
- `app/Http/Resources/` - JsonResource classes for consistent, secure API serialization
- `app/Models/` - Eloquent models with relationships, fillable properties, and casts
- `database/migrations/` - Schema migrations for all domain tables
- `database/seeders/` - Database seeders using data derived from the SQL dump
- `routes/api.php` - All REST API route definitions

## API Testing & Quality Assurance

Full API test cases, Laravel Sanctum authentication setup for Apidog, standardized JSON response formats, and validation rules are documented in [test.md](./test.md).

### Quick Apidog Setup

1. Set Base URL: `http://localhost:8000/api`
2. Set Headers: `Accept: application/json`, `Content-Type: application/json`
3. Configure authentication:
   - Bearer Token: Login via `/login` and attach `Authorization: Bearer {token}`
   - Cookie-based (SPA): Call `GET /sanctum/csrf-cookie` first, then authenticate with cookies enabled
4. Run endpoint tests using the cases provided in [test.md](./test.md)

## Git Workflow & Conventional Commits Guide

### Commit Structure Formula

```text
type(scope): short descriptive message
```

### Prefix Types

| Type | Purpose |
| --- | --- |
| `feat` | A new feature for the API or system. |
| `fix` | A bug fix. |
| `refactor` | Code change that neither fixes a bug nor adds a feature. |
| `docs` | Documentation only changes. |
| `test` | Adding missing tests or correcting existing tests. |
| `chore` | Changes to the build process, auxiliary tools, or libraries. |

### Detailed Examples for the Wildlife Tracking Project

```text
feat(wildlife): add create wildlife endpoint and validation
fix(auth): resolve Sanctum cookie-based CSRF token mismatch
refactor(resource): optimize UserResource data serialization
docs(api): update Scramble endpoint documentation for tracking routes
test(tracking): add feature test for wildlife location updates
chore(deps): update predis package for Redis connection
feat(devices): add tracking device assignment endpoint
fix(gps): correct GPS coordinate validation range
refactor(controllers): thin WildlifeController using ApiResources
docs(readme): add Conventional Commits guide
test(auth): add feature test for login and logout
chore(config): update CORS configuration for Next.js integration
```

### Rules

- Keep the subject line short and imperative.
- Use a scope that reflects the module (e.g., `auth`, `wildlife`, `tracking`, `resource`, `api`, `deps`, `config`).
- Do not end the subject line with a period.
- Separate subject from body with a blank line if adding context.
- Use the body to explain what and why, not how.
- All commits must follow this format for consistency across the team.
