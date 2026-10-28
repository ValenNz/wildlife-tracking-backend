# 🦌 Wildlife Tracking Backend

**wildlife-tracking-backend** is a Laravel 11 API-First / Headless backend built to serve the Next.js frontend of the Wildlife Tracking Monitoring System. It exposes RESTful APIs with strict validation, resource-based serialization, and cookie-based authentication via Laravel Sanctum.

> 📌 **Important for Frontend Developers:** This backend is designed to be consumed by a Next.js (App Router) frontend. Please read the [For Frontend Developers](#-for-frontend-developers) section before integrating.

---

## 🛠 Tech Stack

| Layer | Technology |
| :--- | :--- |
| Framework | Laravel 11 |
| Language | PHP 8.3+ |
| Authentication | Laravel Sanctum 4 (SPA Cookie-based) |
| Database | MySQL / PostgreSQL (via Eloquent ORM) |
| Cache & Queue | Redis (via Predis) |
| API Serialization | Laravel API Resources (`JsonResource`) |
| API Documentation | Scramble (Auto-generated OpenAPI) |
| Testing | Pest PHP |
| Build Tooling | Vite / Tailwind (for any admin panel assets) |

---

## 📦 Prerequisites

Before running the project locally, ensure you have:

- **PHP 8.3+** with extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `zip`
- **Composer** (latest version)
- **Node.js 20+** & NPM (for Vite assets)
- **MySQL 8+** or **PostgreSQL 15+**
- **Redis** (for cache & queue)
- **Git**

---

## 🚀 Installation & Local Setup

### 1. Clone the Repository

```bash
git clone https://github.com/your-org/wildlife-tracking-backend.git
cd wildlife-tracking-backend
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Configuration

Copy the example environment file and configure it:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

#### Key `.env` Variables to Configure

```env
# Application
APP_NAME="Wildlife Tracking"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_FRONTEND_URL=http://localhost:3000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wildlife_tracking
DB_USERNAME=root
DB_PASSWORD=

# Sanctum (CRITICAL for Next.js SPA Auth)
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:8000
SESSION_DOMAIN=localhost
SESSION_DRIVER=cookie

# Redis (Cache & Queue)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# CORS (Allow Next.js to consume API)
CORS_ALLOWED_ORIGINS=http://localhost:3000
CORS_SUPPORTS_CREDENTIALS=true
```

### 4. Database Setup

Run migrations and seed the database:

```bash
php artisan migrate --seed
```

### 5. Start the Development Servers

You need to run **3 processes** simultaneously:

```bash
# Terminal 1: Laravel API Server
php artisan serve

# Terminal 2: Vite (for asset compilation if needed)
npm run dev

# Terminal 3: Queue Worker (for background jobs)
php artisan queue:work redis --tries=3
```

The API will be available at `http://localhost:8000`.
API Documentation (Scramble) will be available at `http://localhost:8000/docs/api`.

---

## 📁 Detailed Folder Structure

```text
wildlife-tracking-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/              # All API controllers (grouped by domain)
│   │   ├── Middleware/           # Custom middleware (e.g., role-based)
│   │   ├── Requests/             # Form Requests for validation
│   │   └── Resources/            # API Resources for JSON serialization
│   ├── Models/                   # Eloquent models
│   ├── Providers/                # Service providers
│   └── Traits/                   # Reusable traits (e.g., ApiResponseTrait)
├── bootstrap/
├── config/
│   ├── cors.php                  # CORS configuration
│   ├── sanctum.php               # Sanctum configuration
│   └── ...
├── database/
│   ├── factories/                # Model factories for testing/seeding
│   ├── migrations/               # Database schema migrations
│   └── seeders/                  # Database seeders
├── public/                       # Public assets (index.php entry point)
├── resources/
├── routes/
│   ├── api.php                   # All API routes (sanctum protected)
│   ├── auth.php                  # Authentication routes (login, logout, user)
│   ├── console.php               # Artisan commands
│   └── web.php                   # Web routes (minimal, mostly for Sanctum CSRF)
├── storage/
│   ├── app/                      # File uploads
│   ├── framework/                # Cache, sessions, views
│   └── logs/                     # Application logs
├── tests/
│   ├── Feature/                  # Feature tests (API endpoints)
│   └── Unit/                     # Unit tests
└── vendor/                       # Composer dependencies
```

### Key Paths Explained

| Path | Purpose |
| :--- | :--- |
| `app/Http/Controllers/Api/` | All REST API controllers. **Never return Eloquent models directly.** |
| `app/Http/Resources/` | `JsonResource` classes for consistent, secure API serialization. |
| `app/Http/Requests/` | Form Request classes for validation. **Always validate here, not in controllers.** |
| `app/Models/` | Eloquent models with relationships, fillable properties, and casts. |
| `app/Traits/ApiResponseTrait.php` | Standardized JSON response helper (see [API Response Standard](#-api-response-standard)). |
| `database/migrations/` | Schema migrations for all domain tables. |
| `routes/api.php` | All REST API route definitions (protected by `auth:sanctum`). |
| `routes/auth.php` | Sanctum SPA authentication routes (login, logout, user, CSRF). |

---

## 📦 API Response Standard

All API endpoints **MUST** return a consistent JSON structure using the `ApiResponseTrait`. This makes it easier for the Next.js frontend to parse responses.

### Success Response

```json
{
  "success": true,
  "message": "Wildlife data retrieved successfully",
  "data": {
    "id": 1,
    "name": "Sumatran Tiger",
    "species": "Panthera tigris sumatrae"
  },
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "total": 100
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."],
    "latitude": ["The latitude must be between -90 and 90."]
  }
}
```

### Usage in Controllers

```php
use App\Traits\ApiResponseTrait;
use App\Http\Resources\WildlifeResource;

class WildlifeController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        $wildlives = Wildlife::paginate(15);
        
        return $this->successResponse(
            data: WildlifeResource::collection($wildlives),
            message: 'Wildlife data retrieved successfully',
            meta: [
                'current_page' => $wildlives->currentPage(),
                'last_page' => $wildlives->lastPage(),
                'total' => $wildlives->total(),
            ]
        );
    }
}
```

---

## 🌿 Git Workflow & Branching Strategy

We use an **Environment-Based Branching** strategy optimized for a small backend team (2 developers) serving a Next.js frontend.

### Branch Types

| Branch | Purpose | Protected? |
| :--- | :--- | :--- |
| `main` | **Production**. Code currently consumed by the live Next.js frontend. | ✅ Yes |
| `staging` | **Integration & UAT**. Where frontend tests new features before production release. | ✅ Yes |
| `feature/[ID]-[name]` | Daily work branch for new features. | ❌ No |
| `hotfix/[ID]-[name]` | Critical bug fixes for production. | ❌ No |

### Workflow Rules

1. **Never commit directly to `main` or `staging`.**
2. **All feature work starts from `staging`:**
   ```bash
   git checkout staging
   git pull origin staging
   git checkout -b feature/PLAT-123-add-wildlife-tracking
   ```
3. **Pull Requests are mandatory.** Every `feature/*` branch must be reviewed by the other backend developer before merging to `staging`.
4. **Release to Production:** Only merge `staging` → `main` when the frontend team confirms the feature is working correctly on the staging environment.
5. **Hotfixes:** Branch from `main`, fix the bug, then merge back to **both `main` AND `staging`** to prevent regression.
6. **Delete feature branches** after they are merged.

### Visual Workflow

```
[main]       ●──────────────────────────────●────────●──> (Live API)
              \                            /          \
               \ (Release)                /            \ (Hotfix)
[staging]       ●────●────●──────────────●              ●──> (Staging API)
                  \    \    \            /
                   \    \    \ (PR)     /
[feature/1]         ●────┼────┼────────┘ 
                     \   \    \
[feature/2]           ●───┼────┼──────────────────────> (BE Dev 2)
                         \    \
[feature/3]               ●────┼──────────────────────> (BE Dev 1)
```

---

## 📝 Conventional Commits Guide

### Commit Structure Formula

```text
type(scope): short descriptive message
```

### Prefix Types

| Type | Purpose |
| :--- | :--- |
| `feat` | A new feature for the API or system. |
| `fix` | A bug fix. |
| `refactor` | Code change that neither fixes a bug nor adds a feature. |
| `docs` | Documentation only changes. |
| `test` | Adding missing tests or correcting existing tests. |
| `chore` | Changes to the build process, auxiliary tools, or libraries. |

### Detailed Examples for Wildlife Tracking

```text
feat(wildlife): add create wildlife endpoint and validation
fix(auth): resolve Sanctum cookie-based CSRF token mismatch
refactor(resource): optimize WildlifeResource data serialization
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

### Commit Rules

- ✅ Keep the subject line short (< 72 chars) and imperative ("add", not "added").
- ✅ Use a scope that reflects the module (e.g., `auth`, `wildlife`, `tracking`, `resource`, `api`, `deps`, `config`).
- ❌ Do not end the subject line with a period.
- ✅ Separate subject from body with a blank line if adding context.
- ✅ Use the body to explain **what** and **why**, not how.
- ✅ All commits must follow this format for consistency across the team.

---

## 🧪 Testing

We use **Pest PHP** for testing. All API endpoints must have corresponding Feature Tests.

### Running Tests

```bash
# Run all tests
php artisan test

# Run only Feature tests
php artisan test --testsuite=Feature

# Run a specific test file
php artisan test tests/Feature/WildlifeTest.php
```

### Writing a Test Example

```php
// tests/Feature/WildlifeTest.php
use App\Models\User;
use App\Models\Wildlife;

test('authenticated user can list wildlife', function () {
    $user = User::factory()->create();
    Wildlife::factory()->count(5)->create();

    $response = $this->actingAs($user)->getJson('/api/wildlife');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => ['id', 'name', 'species', 'last_location']
            ],
            'meta' => ['current_page', 'last_page', 'total']
        ]);
});

test('unauthenticated user cannot list wildlife', function () {
    $response = $this->getJson('/api/wildlife');
    $response->assertStatus(401);
});
```

---

## 🎨 For Frontend Developers

### Authentication Flow (Sanctum SPA)

This backend uses **cookie-based authentication** (not Bearer tokens). The flow is:

1. **Get CSRF Cookie:** `GET /sanctum/csrf-cookie` (must be called first)
2. **Login:** `POST /login` with `{ email, password }`
3. **Authenticated Requests:** Subsequent requests will automatically include the session cookie.
4. **Logout:** `POST /logout`
5. **Get User:** `GET /api/user`

### CORS Configuration

The backend is configured to accept requests from the Next.js frontend. Ensure your Next.js app URL is listed in `.env`:

```env
CORS_ALLOWED_ORIGINS=http://localhost:3000
SANCTUM_STATEFUL_DOMAINS=localhost:3000
```

### Fetching Data from Next.js

When fetching data from Next.js (especially Server Components), you must include credentials:

```typescript
// app/actions.ts (Next.js Server Action or Server Component)
const response = await fetch('http://localhost:8000/api/wildlife', {
  method: 'GET',
  credentials: 'include', // CRITICAL: Sends Sanctum cookie
  cache: 'no-store',      // Or 'force-cache' with revalidation
  next: { tags: ['wildlife'] } // For on-demand revalidation
});

const data = await response.json();
// data.success, data.data, data.meta
```

### API Documentation

Live API documentation (auto-generated by Scramble) is available at:
- **Local:** `http://localhost:8000/docs/api`
- **Staging:** `https://staging-api.domain.com/docs/api`

---

## ⚡ Useful Commands

```bash
# Clear all caches
php artisan optimize:clear

# Cache config, routes, and views (for production)
php artisan optimize

# Create a new API controller
php artisan make:controller Api/WildlifeController --api

# Create a new API Resource
php artisan make:resource WildlifeResource

# Create a new Form Request
php artisan make:request StoreWildlifeRequest

# Create a new Feature Test
php artisan make:test WildlifeTest

# Run database migrations
php artisan migrate

# Seed the database
php artisan db:seed

# Monitor queue jobs (requires Laravel Horizon)
php artisan horizon
```

---

## 🤝 Collaboration SOP (BE ↔ FE)

1. **API Contract First:** Before coding, BE must define the JSON structure (request/response) and share it with FE (via Scramble docs or a simple JSON snippet).
2. **Staging First:** FE should always test against `staging-api.domain.com`, never `production`.
3. **No Breaking Changes:** Never remove or rename existing JSON fields in `main`. If a change is needed, add new fields (backward compatible) or use API versioning (`/api/v2/...`).
4. **Revalidation:** When BE updates data, it should trigger a revalidation webhook to Next.js (see `/api/trigger-revalidate` endpoint) to clear SSR cache.

---

## 📄 License

This project is proprietary and confidential.
