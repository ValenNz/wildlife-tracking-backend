# Wildlife Tracking – Backend

REST API untuk **Wildlife Tracking Monitoring System**. Backend bertanggung jawab atas **business logic, authentication, authorization, validation, REST API, integrasi AI,** dan komunikasi dengan database.

**Stack:** Laravel · PHP · MVC · Eloquent · Laravel Sanctum · MySQL

Repo terkait: `wildlife-tracking-frontend` (UI) · `wildlife-tracking-database` (desain DB)

> Folder `database/` (migration, seeder, factory) ada di repo ini karena Laravel yang menjalankannya, tetapi **dimiliki PIC Database**.

---

## 1. SETUP UNTUK PEMULA (ikuti urut)

### 1.1 Install aplikasi
Cara paling mudah di **Windows: Laragon (versi Full)** – sudah termasuk PHP, MySQL, dan Composer.
| Aplikasi | Link | Cek berhasil |
|---|---|---|
| Laragon (Full) *atau* XAMPP + Composer | https://laragon.org | `php -v`, `composer -V` |
| Git | https://git-scm.com | `git --version` |
| VS Code | https://code.visualstudio.com | – |
| Postman (uji API) | https://www.postman.com | – |

Versi PHP harus memenuhi syarat Laravel yang dipakai (lihat `composer.json` → `"php"`). **Semua anggota memakai versi PHP yang sama.**
Ekstensi VS Code: **PHP Intelephense**, **Laravel Blade Snippets**, **DotENV**.

Mac/Linux: gunakan Laravel Herd (Mac) atau install PHP + Composer + MySQL manual.

### 1.2 Set identitas Git (sekali saja)
```bash
git config --global user.name "Nama Kamu"
git config --global user.email "email-github-kamu@example.com"
```

### 1.3 Clone & jalankan project
```bash
git clone https://github.com/<org>/wildlife-tracking-backend.git
cd wildlife-tracking-backend
git checkout develop
composer install
```
Buat file environment:
```bash
cp .env.example .env        # PowerShell: copy .env.example .env
php artisan key:generate
```
Nyalakan **MySQL** (di Laragon klik *Start All*). Buat database kosong bernama `wildlife_tracking` (lewat HeidiSQL/phpMyAdmin/DBeaver, atau perintah: `CREATE DATABASE wildlife_tracking;`).

Edit `.env`:
```
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wildlife_tracking
DB_USERNAME=root
DB_PASSWORD=
AI_API_URL=
AI_API_KEY=
```
(`DB_PASSWORD` di Laragon/XAMPP default kosong. Isi `AI_API_KEY` hanya di `.env` lokal, **jangan dibagikan di grup/Git**; minta ke PM.)

Buat tabel + data contoh, lalu jalankan:
```bash
php artisan migrate --seed
php artisan serve
```
Buka http://localhost:8000/api/ping (jika sudah dibuat) atau uji lewat Postman. Jika `php artisan serve` jalan tanpa error ✅.

Reset database kapan saja (menghapus semua data lokal!):
```bash
php artisan migrate:fresh --seed
```

### 1.4 Masalah umum
| Masalah | Solusi |
|---|---|
| `php/composer is not recognized` | Install Laragon / tambahkan PHP ke PATH, restart terminal |
| `SQLSTATE[HY000] [1049] Unknown database` | Database `wildlife_tracking` belum dibuat |
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL belum menyala |
| `No application encryption key` | Jalankan `php artisan key:generate` |
| Setelah ubah `.env` tidak berpengaruh | `php artisan config:clear` |
| Error CORS di frontend | Cek `config/cors.php` & `FRONTEND_URL` |
| `Class not found` setelah pull | `composer install` lalu `composer dump-autoload` |
| Migration error setelah pull | `php artisan migrate`, atau `migrate:fresh --seed` di lokal |

---

## 2. SETUP PROJECT DARI NOL (HANYA PIC Backend, sekali saja)
```bash
composer create-project laravel/laravel .
php artisan install:api          # menyiapkan routes/api.php + Laravel Sanctum
```
- Tambahkan `FRONTEND_URL` ke `.env.example`, atur `config/cors.php` agar `allowed_origins` = `[env('FRONTEND_URL')]`, `paths` = `['api/*']`.
- Tambahkan konfigurasi AI di `config/services.php`:
  ```php
  'ai' => ['url' => env('AI_API_URL'), 'key' => env('AI_API_KEY')],
  ```
- Pastikan `.env` ada di `.gitignore` dan `.env.example` **tanpa nilai rahasia**.
- Commit ke `develop`, minta semua anggota clone.

`.github/CODEOWNERS` (opsional, memaksa review PIC Database untuk skema):
```
/database/ @username-pic-database
```

---

## 3. STRUKTUR FOLDER
```
app/
├── Http/
│   ├── Controllers/   # terima request → panggil service/model → return response
│   ├── Requests/      # validasi input (StoreWildlifeRequest)
│   └── Resources/     # format output JSON (WildlifeResource)
├── Models/            # Eloquent + relasi
├── Policies/          # otorisasi per model (dibuat saat diperlukan)
└── Services/          # logic kompleks
    └── Ai/            # WildlifeSummaryService.php
database/              # (milik PIC Database) migrations, seeders, factories
routes/api.php         # daftar endpoint
tests/                 # Feature & Unit test
docs/                  # api.md, architecture.md, ai-integration.md
```

---

## 4. ATURAN MVC
| Komponen | Tugas | Dilarang |
|---|---|---|
| Model | Data, relasi, scope | Logic request/response |
| Controller | Terima request, panggil service/model, return | Query panjang, validasi manual, logic AI |
| Request | Validasi + `authorize()` | Query berat |
| Resource | Format response | Business logic |
| Service | Logic kompleks (hitung, AI, multi-model) | Memakai `request()` langsung |

Service **hanya dibuat jika logic lebih dari CRUD sederhana**.

Membuat file dengan artisan:
```bash
php artisan make:model Wildlife
php artisan make:controller WildlifeController --api
php artisan make:request StoreWildlifeRequest
php artisan make:resource WildlifeResource
php artisan make:policy WildlifePolicy --model=Wildlife
php artisan make:test WildlifeTest
```
```php
class WildlifeController extends Controller
{
    public function __construct(private WildlifeService $service) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Wildlife retrieved successfully',
            'data' => WildlifeResource::collection($this->service->paginate()),
        ]);
    }

    public function store(StoreWildlifeRequest $request): JsonResponse
    {
        $wildlife = $this->service->create($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Wildlife created successfully',
            'data' => new WildlifeResource($wildlife),
        ], 201);
    }
}
```
```php
class StoreWildlifeRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('create', Wildlife::class); }
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'species_id' => ['required', 'exists:species,id'],
            'status' => ['required', 'in:active,inactive,deceased,unknown'],
        ];
    }
}
```

## 5. REST API
```
GET    /api/wildlife        POST   /api/wildlife
GET    /api/wildlife/{id}   PUT    /api/wildlife/{id}   DELETE /api/wildlife/{id}
```
| Code | Kapan |
|---|---|
| 200 | GET/PUT berhasil |
| 201 | POST berhasil membuat data |
| 204 | DELETE berhasil tanpa body |
| 400 | Request salah/tidak bisa diproses |
| 401 | Belum login / token tidak valid |
| 403 | Login tapi tidak punya izin |
| 404 | Data tidak ditemukan |
| 422 | Validasi gagal |
| 500 | Error server tak terduga |

**Format response (WAJIB sama di semua endpoint):**
```json
{ "success": true,  "message": "Wildlife retrieved successfully", "data": [] }
{ "success": false, "message": "Wildlife not found", "data": null }
{ "success": false, "message": "Validation failed", "errors": { "name": ["The name field is required."] } }
```
Setiap endpoint baru **wajib dicatat di `docs/api.md`** (method, URL, role, request, response) agar Frontend bisa memakainya.

## 6. AUTHENTICATION & AUTHORIZATION
- **Authentication** = "siapa pengguna?" → login mengembalikan token Sanctum; FE mengirim `Authorization: Bearer <token>`.
- **Authorization** = "apa yang boleh dilakukan?" → Policy/Gate di backend.

| Role | Hak akses |
|---|---|
| Admin | Semua, termasuk kelola user |
| Researcher | CRUD satwa/monitoring/laporan, fitur AI |
| Observer | Input sighting & monitoring, lihat data |
| User | Hanya baca data publik |
```php
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('wildlife', WildlifeController::class);
});
```

## 7. INTEGRASI AI
```
Next.js → Laravel (AiController) → WildlifeSummaryService → AI API → simpan ke ai_summaries → Next.js
```
```php
class WildlifeSummaryService
{
    public function summarize(Wildlife $wildlife): string
    {
        $records = $wildlife->monitoringRecords()->latest('recorded_at')->limit(20)->get();
        $prompt = "Ringkas kondisi satwa berikut dalam Bahasa Indonesia:\n" . $records->toJson();

        $res = Http::withToken(config('services.ai.key'))->timeout(30)
            ->post(config('services.ai.url'), ['prompt' => $prompt]);

        if ($res->failed()) {
            throw new AiServiceException('AI service unavailable');
        }
        return $res->json('text');
    }
}
```
Aturan: API key hanya di `.env` · jangan kirim data user/credential ke AI · pakai timeout & tangani gagal · aplikasi tetap jalan jika AI gagal · di test pakai `Http::fake()`.

## 8. ERROR HANDLING
Semua error memakai format response di bagian 5. Tangani `ModelNotFound` (404), `Authentication` (401), `Authorization` (403), `Validation` (422). Di production `APP_DEBUG=false`. **Jangan** tampilkan stack trace, kredensial DB, API key, atau info server ke user; detailnya masuk log (`storage/logs`).

## 9. TESTING
```bash
php artisan test
```
Minimal per endpoint utama: sukses, 401, 403, 404, dan 422.
```php
public function test_researcher_can_create_wildlife(): void
{
    Sanctum::actingAs(User::factory()->create(['role' => 'researcher']));
    $this->postJson('/api/wildlife', [
        'name' => 'Rimba',
        'species_id' => Species::factory()->create()->id,
        'status' => 'active',
    ])->assertCreated()->assertJsonPath('success', true);
}
```

## 10. NAMING
Controller `WildlifeController` · Model `Wildlife` (singular) · Request `StoreWildlifeRequest` · Resource `WildlifeResource` · Service `WildlifeService` · Tabel/kolom `snake_case` jamak (`monitoring_records`) · URL `kebab-case`.

## 11. GIT WORKFLOW (CONTEKAN PEMULA)
Branch: `main`, `develop`, `feature/<nama>`, `fix/<nama>`.
```bash
git checkout develop
git pull origin develop
git checkout -b feature/wildlife-api

# ... coding ...
php artisan test
git add .
git commit -m "feat(wildlife): add wildlife CRUD endpoints"
git push -u origin feature/wildlife-api
```
Buka Pull Request ke `develop` → minta review. Commit: `feat:` `fix:` `refactor:` `docs:` `test:` `chore:`.
Konflik? `git pull origin develop` di branch kamu → selesaikan di VS Code → `git add .` → `git commit`.
Setelah `git pull`, jalankan `composer install` dan `php artisan migrate` bila ada perubahan.

**Dilarang:** push ke `main`/`develop`, commit `.env`, menyimpan API key di kode.

## 12. DEFINITION OF DONE (Backend)
- [ ] Migration & model tersedia (dengan PIC Database)
- [ ] Endpoint + Request validation + Resource
- [ ] Authorization (Policy) bila perlu
- [ ] Response sesuai format standar
- [ ] `docs/api.md` diperbarui
- [ ] Feature test lulus (`php artisan test`)
- [ ] Tidak ada credential ter-commit
- [ ] PR sudah di-review

## 13. DO & DON'T
| ✅ DO | ❌ DON'T |
|---|---|
| Validasi di Form Request | Validasi manual di controller |
| Cek izin di backend | Percaya UI frontend |
| Simpan secret di `.env` | Hard-code API key |
| Controller tipis | Query panjang & logic AI di controller |
| Ubah DB lewat migration | Ubah tabel manual di phpMyAdmin |

## 14. Tanggung jawab
Laravel · REST API · MVC · business logic · authentication · authorization · validation · integrasi AI.
