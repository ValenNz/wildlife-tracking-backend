# 🐾 Wildlife Monitoring System — Backend (Laravel REST API)

> **Klien:** Balai Taman Nasional Gunung Leuser (BTNGL)
> **Repo ini:** REST API Laravel + Eloquent + Sanctum
> **PIC Backend:** **Yohanna Purnomo**
> **PIC Database:** **Nuevalen Refitra Alswando, Laura Chyndearni Saragih**

Dokumen ini adalah **sumber kebenaran tunggal** untuk backend. Semua penjelasan ditulis dalam Bahasa Indonesia; nama file, kode, dan istilah teknis tetap dalam Bahasa Inggris.

---

## 👥 Tim Proyek

| Nama | Peran |
| ---- | ----- |
| Farrellino Ulung Satya Amando | PIC AI & Integration |
| **Nuevalen Refitra Alswando** | **PIC Database** |
| Laura Chyndearni Saragih | PIC Database |
| **Yohanna Purnomo** | **PIC Backend & PIC Documentation** |
| Yohana Sinaga | PIC Frontend |

Repo terkait: `wildlife-tracking-frontend` (Next.js) · `wildlife-tracking-database` (desain skema)

---

## 📑 Daftar Isi

1. [Gambaran Umum](#1-gambaran-umum)
2. [Tech Stack](#2-tech-stack)
3. [Struktur Proyek](#3-struktur-proyek)
4. [Skema Database (14 Tabel)](#4-skema-database-14-tabel)
5. [Model & Relasi Eloquent](#5-model--relasi-eloquent)
6. [Autentikasi & Otorisasi (Sanctum)](#6-autentikasi--otorisasi-sanctum)
7. [Endpoint API](#7-endpoint-api)
8. [Seeder & Data Uji](#8-seeder--data-uji)
9. [Cara Menjalankan](#9-cara-menjalankan)
10. [Konvensi Coding](#10-konvensi-coding-wajib)
11. [Status Sprint & Catatan](#11-status-sprint--catatan)
12. [Masalah Umum & Solusi](#12-masalah-umum--solusi)

---

## 1. Gambaran Umum

**Sistem Pelacakan Pergerakan Satwa Liar Berbasis Sensor GPS** adalah aplikasi untuk memantau pergerakan satwa endemik terancam punah di Taman Nasional Gunung Leuser — **Orangutan Sumatra**, **Harimau Sumatra**, **Badak Sumatra**, **Gajah Sumatra**, dan **Beruang Madu** — melalui GPS collar.

Backend menyediakan:
- REST API untuk CRUD master data (satwa, spesies, perangkat, zona)
- Endpoint GPS readings, zona geofence, laporan insiden
- Autentikasi token berbasis Laravel Sanctum
- Relasi Eloquent lengkap untuk 14 tabel domain + `personal_access_tokens` (Sanctum)

Frontend **tidak pernah** menyentuh database — semua lewat API ini.

---

## 2. Tech Stack

| Lapisan | Teknologi | Versi |
| ------- | --------- | ----- |
| Framework | Laravel | `^13.17` |
| Bahasa | PHP | `^8.3` |
| Auth | Laravel Sanctum | `^4` |
| Database | MySQL (prod) / SQLite (dev) | 8.0 / 3.x |
| Testing | PHPUnit + Pest | `^12` |
| Formatter | Laravel Pint | — |
| Asset Build | Vite + Tailwind CSS 4 | — |

> **Catatan:** Brief awal menyebut Laravel 11 / PHP 8.2. Manifest `composer.json` terpasang adalah **Laravel 13.17 / PHP 8.3**. Ikuti manifest.

---

## 3. Struktur Proyek

```
wildlife-tracking-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php       # login, logout, me
│   │   │   │   ├── AnimalController.php
│   │   │   │   ├── SpeciesController.php
│   │   │   │   ├── TrackingDeviceController.php
│   │   │   │   ├── GeoZoneController.php
│   │   │   │   ├── GPSReadingController.php
│   │   │   │   ├── AnimalGeoZoneController.php
│   │   │   │   ├── IncidentReportController.php
│   │   │   │   ├── AIMediaUploadController.php
│   │   │   │   ├── AIPredictionController.php
│   │   │   │   ├── WeatherDataController.php
│   │   │   │   ├── NotificationController.php
│   │   │   │   ├── AggregateReportController.php
│   │   │   │   ├── ActivityLogController.php
│   │   │   │   └── DashboardController.php
│   │   │   └── Controller.php
│   │   ├── Middleware/
│   │   ├── Requests/          # FormRequest (belum ada — tambah saat perlu)
│   │   └── Resources/         # ApiResource (belum ada — tambah saat perlu)
│   ├── Models/                # 22 Model Eloquent
│   │   ├── User.php
│   │   ├── Species.php
│   │   ├── Animal.php
│   │   ├── TrackingDevice.php
│   │   ├── GeoZone.php
│   │   ├── GPSReadings.php
│   │   ├── AnimalGeoZone.php
│   │   ├── IncidentReport.php
│   │   ├── AIMediaUpload.php
│   │   ├── AIPrediction.php
│   │   ├── WeatherData.php
│   │   ├── Notification.php
│   │   ├── AggregateReport.php
│   │   ├── ActivityLog.php
│   │   ├── Visitor.php
│   │   ├── TicketType.php
│   │   ├── Booking.php
│   │   ├── BookingItem.php
│   │   ├── Payment.php
│   │   ├── Role.php
│   │   ├── Permission.php
│   │   └── RolePermission.php
│   ├── Services/              # Business logic (opsional, buat saat perlu)
│   └── Providers/
├── bootstrap/
│   └── app.php                # Config middleware, redirectGuestsTo(null)
├── config/
│   ├── auth.php               # sanctum guard
│   ├── cors.php               # allow localhost:3000
│   └── sanctum.php
├── database/
│   ├── migrations/            # 30+ file migrasi (14 tabel + modify + baru)
│   ├── seeders/               # 22 seeder
│   │   ├── DatabaseSeeder.php
│   │   ├── SpeciesSeeder.php
│   │   ├── UserSeeder.php
│   │   ├── AnimalSeeder.php
│   │   ├── TrackingDeviceSeeder.php
│   │   ├── GeoZoneSeeder.php
│   │   ├── GpsReadingSeeder.php
│   │   ├── AnimalGeoZoneSeeder.php
│   │   ├── IncidentReportSeeder.php
│   │   ├── AiMediaUploadSeeder.php
│   │   ├── AiPredictionSeeder.php
│   │   ├── WeatherDataSeeder.php
│   │   ├── NotificationSeeder.php
│   │   ├── AggregateReportSeeder.php
│   │   ├── ActivityLogSeeder.php
│   │   ├── VisitorSeeder.php
│   │   ├── TicketTypeSeeder.php
│   │   ├── BookingSeeder.php
│   │   ├── BookingItemSeeder.php
│   │   ├── PaymentSeeder.php
│   │   ├── RoleSeeder.php
│   │   ├── PermissionSeeder.php
│   │   └── RolePermissionSeeder.php
│   └── factories/             # Factory (opsional)
├── routes/
│   ├── api.php                # Semua route API
│   └── web.php                # Hanya untuk welcome.blade.php
├── .env.example               # Template env
├── composer.json
├── phpunit.xml
└── README.md                  # File ini
```

---

## 4. Skema Database (14 Tabel)

Semua migrasi berada di `database/migrations/`. **Primary key selalu bernama domain** (`user_id`, `species_id`, `animal_id`, …), **bukan** `id`.

| Tabel | Primary Key | Foreign Keys | Keterangan |
| ----- | ----------- | ------------ | ---------- |
| `users` | `user_id` | — | Petugas, peneliti, admin, pengambil kebijakan |
| `species` | `species_id` | — | Data referensi spesies |
| `animals` | `animal_id` | `species_id` → `species` | Satwa individual |
| `tracking_devices` | `device_id` | `animal_id` → `animals` | GPS collar |
| `geo_zones` | `zone_id` | `created_by` → `users` | Zona risiko & lindung |
| `gps_readings` | `reading_id` | `device_id` → `tracking_devices` | Ping posisi mentah |
| `animal_geo_zones` | `visit_id` | `animal_id`, `zone_id`, `entry_reading_id` | Event masuk/keluar zona |
| `incident_reports` | `report_id` | `user_id` → `users` | Laporan lapangan |
| `ai_media_uploads` | `media_id` | `uploaded_by`, `report_id` | Foto/video dari lapangan |
| `ai_predictions` | `prediction_id` | `media_id`, `predicted_species_id`, `verified_species_id`, `verified_by` | Identifikasi AI + verifikasi manusia |
| `weather_data` | `weather_id` | `reading_id` → `gps_readings` | Cuaca per reading |
| `notifications` | `notif_id` | `user_id`, `animal_id`, `zone_id`, `device_id` | Alert risiko & device |
| `activity_log` | `log_id` | `user_id` → `users` | Audit trail |
| `aggregate_reports` | `report_id` | `generated_by` → `users` | Laporan periodik |
| `personal_access_tokens` | `id` | `tokenable` | Sanctum (bukan domain) |

**Tabel tambahan (booking system):**
- `visitors`, `ticket_types`, `bookings`, `booking_items`, `payments`
- `roles`, `permissions`, `role_permissions` (RBAC terpisah dari `users.role`)

### 4.1 Perbedaan dengan TUBES / Week 5

| Tabel | Implementasi | TUBES | Status |
| ----- | ------------ | ----- | ------ |
| `weather_data` | `reading_id` → `gps_readings` | `zone_id` → `geo_zones` | **Real** — ikuti implementasi |
| `incident_reports` | `user_id` | `reported_by` | Naming only; sama-sama ke `users` |
| `gps_readings` | Ada `altitude_meters` | Tidak ada | Additif, aman |
| `ai_predictions` | Ada `verified_by`, `verified_species_id` | Tidak ada | Additif — trail verifikasi |
| `users.password` | Kolom `password` | `password_hash` | **Laravel convention** — jangan ganti |

---

## 5. Model & Relasi Eloquent

Semua model di `app/Models/`. Konvensi:
- `protected $table` = nama tabel snake_case (`geo_zones`, `gps_readings`, `tracking_devices`, dst.)
- `$primaryKey` = domain key (`user_id`, `species_id`, dst.)
- `public $timestamps = false` untuk tabel tanpa `created_at`/`updated_at`
- `casts()` untuk enum, decimal, date, boolean

### Relasi utama

```php
// User
$user->incidentReports()          // hasMany IncidentReport
$user->geoZones()                 // hasMany GeoZone (created_by)
$user->aiMediaUploads()           // hasMany AIMediaUpload
$user->aiPredictionsVerified()    // hasMany AIPrediction (verified_by)
$user->activityLogs()             // hasMany ActivityLog
$user->aggregateReports()         // hasMany AggregateReport
$user->notifications()            // hasMany Notification

// Animal
$animal->species()                // belongsTo Species
$animal->trackingDevice()         // hasOne TrackingDevice
$animal->geoZoneVisits()          // hasMany AnimalGeoZone
$animal->notifications()          // hasMany Notification

// TrackingDevice
$device->animal()                 // belongsTo Animal
$device->gpsReadings()            // hasMany GPSReadings
$device->notifications()          // hasMany Notification

// GPSReadings
$reading->device()                // belongsTo TrackingDevice
$reading->zoneVisits()            // hasMany AnimalGeoZone (entry_reading_id)
$reading->weatherData()           // hasMany WeatherData

// AnimalGeoZone
$visit->animal()                  // belongsTo Animal
$visit->zone()                    // belongsTo GeoZone
$visit->entryReading()            // belongsTo GPSReadings

// IncidentReport
$report->reporter()               // belongsTo User
$report->mediaUploads()           // hasMany AIMediaUpload

// AIMediaUpload
$upload->uploader()               // belongsTo User
$upload->incidentReport()         // belongsTo IncidentReport
$upload->predictions()            // hasMany AIPrediction

// AIPrediction
$pred->media()                    // belongsTo AIMediaUpload
$pred->predictedSpecies()         // belongsTo Species
$pred->verifiedSpecies()          // belongsTo Species
$pred->verifier()                 // belongsTo User

// WeatherData
$weather->reading()               // belongsTo GPSReadings

// Notification
$notif->user()                    // belongsTo User
$notif->animal()                  // belongsTo Animal
$notif->zone()                    // belongsTo GeoZone
$notif->device()                  // belongsTo TrackingDevice

// AggregateReport
$report->generator()              // belongsTo User
```

---

## 6. Autentikasi & Otorisasi (Sanctum)

### Login
```
POST /api/login
{ "username": "...", "password": "..." }
```
- **Username**, bukan email
- Response: `{ success, message, data: { token, user }, errors: null }`
- Token Sanctum disimpan di tabel `personal_access_tokens`
- **Login kedua meng-invalidate token pertama** (`$user->tokens()->delete()`)

### Protected Routes
Semua route data pakai middleware `auth:sanctum`:
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('animals', AnimalController::class)->only(['index']);
    Route::apiResource('species', SpeciesController::class)->only(['index']);
    // dst...
});
```

### Response 401 / 422
```json
// 401 Wrong credentials
{ "success": false, "message": "Unauthenticated.", "data": null, "errors": { "credentials": ["Kredensial tidak valid."] } }

// 422 Validation
{ "success": false, "message": "Validasi gagal.", "data": null, "errors": { "username": ["Username wajib diisi."] } }
```

### CORS
`config/cors.php` sudah allow `http://localhost:3000`.

### bootstrap/app.php
```php
$middleware->redirectGuestsTo(fn () => null);
```
Tanpa ini, unauthenticated API call return 500 "Route [login] not defined".

---

## 7. Endpoint API

Base URL: `http://localhost:8000/api`

| Method | Path | Controller | Status | Catatan |
| ------ | ---- | ---------- | ------ | ------- |
| **Auth (publik)** |
| POST | `/login` | `AuthController@login` | ✅ | Username + password |
| **Protected (auth:sanctum)** |
| GET | `/me` | `AuthController@me` | ✅ | Current user |
| POST | `/logout` | `AuthController@logout` | ✅ | Revoke token |
| GET | `/animals` | `AnimalController@index` | ✅ | Bare array |
| GET | `/species` | `SpeciesController@index` | ✅ | Bare array |
| GET | `/devices` | `TrackingDeviceController@index` | ✅ | `{success, data}` |
| GET | `/devices/{id}` | `TrackingDeviceController@show` | ✅ | |
| GET | `/gps-readings` | `GPSReadingController@index` | ✅ | Limit 100, ada `total` |
| GET | `/gps-readings/{animal_id}` | `GPSReadingController@byAnimal` | ✅ | |
| GET | `/incidents` | `IncidentReportController@index` | ✅ | Bare array |
| GET | `/notifications` | `NotificationController@index` | ✅ | Bare array |
| GET | `/geo-zones` | `GeoZoneController@index` | ❌ **Rusak** | Class `Geozones` vs `GeoZonesController` |
| GET | `/dashboard` | `DashboardController@index` | ❌ **Rusak** | Method `summary()` bukan `index()` |
| GET | `/export/gps` | `ExportController@gps` | ❌ **Rusak** | Method `csv` & `geojson` saja |

> **Semua route write (POST/PUT/DELETE) BELUM ADA** kecuali `/login` & `/logout`.
> Jangan buat FormRequest / ApiResource / Policy sebelum route write ada.

---

## 8. Seeder & Data Uji

Jalankan:
```bash
php artisan migrate:fresh --seed
```

### Akun Uji (dari `UserSeeder`)

| Username | Password | Role |
| -------- | -------- | ---- |
| `admin_btnl` | `admin123` | `administrator_sistem` |
| `konservasis_bukitlawang` | `password` | `konservasionis_lapangan` |
| `konservasis_kualabatang` | `password` | `konservasionis_lapangan` |
| `peneliti_leuser` | `password` | `peneliti_ekologi` |
| `kepala_balai` | `password` | `pengambil_kebijakan` |

> ⚠️ **Jangan ada spasi di depan nilai enum `role`** — akan gagal CHECK constraint SQLite.

### Data Domain Realistis
- **Species:** 5 spesies endemik Leuser (Orangutan, Harimau, Badak, Gajah, Beruang Madu)
- **Animals:** Kode collar `SATWA-742`, `SATWA-281`, `SATWA-803`, `SATWA-387`, `SATWA-968`
- **GeoZones:** 5 zona nyata (koridor ekologis, perkebunan, permukiman, jalan raya)
- **GPS:** Koordinat dalam rentang Leuser (lat `3.5–3.7`, long `97.9–98.2`)

---

## 9. Cara Menjalankan

### Prasyarat
- PHP `^8.3` + Composer
- MySQL 8.0 (prod) / SQLite (dev)
- Node.js + npm (untuk Vite build welcome page)

### Setup Pertama Kali
```bash
cd wildlife-tracking-backend
composer install                          # WAJIB — vendor/ tidak di-commit
cp .env.example .env
php artisan key:generate
```

### Database
**SQLite (dev cepat):**
```bash
# .env sudah punya DB_CONNECTION=sqlite
php artisan migrate:fresh --seed
php artisan serve                         # :8000
```

**MySQL (produksi/ CI):**
```bash
# Edit .env → DB_CONNECTION=mysql, DB_DATABASE=wildlife_tracking, dll.
php artisan migrate:fresh --seed
php artisan serve
```

### Development Commands
```bash
php artisan test                          # atau: composer test
vendor/bin/pint                           # formatter (tidak ada script composer)
npm install && npm run build              # Vite untuk welcome.blade.php
```

---

## 10. Konvensi Coding (WAJIB)

### Layering (belum full tapi target)
| Layer | Tanggung Jawab |
| ----- | -------------- |
| **Model** | Data, relasi, casts, accessor/mutator |
| **Controller** | Tipis: validasi → service → response |
| **FormRequest** | Validasi input (buat saat route write ada) |
| **ApiResource** | Format response konsisten (buat saat route write ada) |
| **Service** | Business logic kompleks, transaksi DB |
| **Policy/Gate** | Otorisasi per aksi |

### Migration
- **Selalu** migration baru untuk perubahan schema — **jangan edit** migrasi yang sudah di-merge.
- Gunakan `constrained('table', 'column')` eksplisit:
  ```php
  $table->foreignId('species_id')->constrained('species', 'species_id');
  ```
- Enum pakai `string` + CHECK constraint (bukan native enum) biar portable SQLite ↔ MySQL.

### Seeder
- Pakai `updateOrCreate` biar aman di-re-run.
- Data domain **harus realistis** untuk Gunung Leuser — **jangan Faker** untuk satwa/zona.

### Password & Secret
- **Selalu hash** dengan `Hash::make()` / cast `hashed`.
- **Jangan** log, echo, atau return password di response.
- `.env` **tidak di-commit** (sudah di `.gitignore`).

### GPS Validation
- Latitude: `-90` s/d `90`
- Longitude: `-180` s/d `180`
- Validasi di **FormRequest**, bukan di model.

---

## 11. Status Sprint & Catatan

### Sprint 1 — Login Use Case ✅
- `POST /api/login`, `GET /api/me`, `POST /api/logout` — done & tested
- Token rotation verified (login kedua invalidate token pertama)

### Sprint 2+ — Belum Dimulai
| Modul | Status | Blocker |
| ----- | ------ | ------- |
| Master Data CRUD (Animals, Species, Devices) | ⏳ Menunggu | Route write + FormRequest + Resource |
| GPS Readings (pagination, filter) | ⏳ | Controller hanya `index` bare array |
| Geo Zones | ❌ **Rusak** | `GeoZonesController` vs `GeozonesController` (case-sensitive) |
| Incident Reports | ⏳ | Hanya GET |
| AI Module (upload, prediksi, verifikasi) | ⏳ | Endpoint belum ada |
| Dashboard Summary | ❌ **Rusak** | Method `summary()` tapi route pakai `index()` |
| Export GPS (CSV/GeoJSON) | ⚠️ Sebagian | Route `/export/gps` tidak ada; pakai `/csv` & `/geojson` |

---

## 12. Masalah Umum & Solusi

| Gejala | Penyebab | Solusi |
| ------ | -------- | ------ |
| `migrate:fresh` gagal FK `booking_items → bookings` | Ada migrasi duplikat `2026_01_10_000002_create_booking*.php` | Hapus file lama, pakai `2026_10_20_09150*_create_*.php` |
| `role_permissions` FK error `role_id` incompatible | `roles` pakai `$table->id('role_id')` (unsignedBigInteger), pivot pakai `unsignedInteger` | Samakan: pivot pakai `unsignedBigInteger` |
| `geo-zones` 500 error | Class `Geozones` tapi controller import `GeoZonesController` | Rename file/class: `GeozonesController` → `GeoZonesController` |
| `dashboard` 500 error | Route `Route::get('/dashboard', [DashboardController::class, 'index'])` tapi method-nya `summary()` | Tambah method `index()` yang call `summary()` atau ganti route |
| `export/gps` 404 | Route polos `/export/gps` tidak ada | Pakai `/export/gps/csv` dan `/export/gps/geojson` |
| Semua request 401 "Unauthenticated" | Cookie token kosong / nama cookie salah | Cek `.env` `NEXT_PUBLIC_COOKIE_TOKEN=wms_token` (jangan kosong) |
| Migration `dropUnique` gagal di SQLite | Index name beda | Hapus `$table->dropUnique(['column'])` biarkan Laravel handle saat `dropColumn` |
| `activitylog` column `created_at` not found | Migrasi rename `created_at` → `timestamp` tapi model masih cast `created_at` | Update model `casts()` pakai `timestamp` |

---

## 13. Referensi

- [Laravel 13.x Docs](https://laravel.com/docs/13.x)
- [Sanctum 4.x](https://laravel.com/docs/13.x/sanctum)
- [Eloquent Relationships](https://laravel.com/docs/13.x/eloquent-relationships)
- [Database Migrations](https://laravel.com/docs/13.x/migrations)
- [API Resources](https://laravel.com/docs/13.x/eloquent-resources)

---

## 14. Kontak

| PIC | Hubungan |
| --- | -------- |
| **Yohanna Purnomo** | Backend & Documentation — koordinasi API, route, migration |
| **Nuevalen / Laura** | Database — skema, FK, seeder, data domain |
| **Farrellino** | AI Integration — endpoint upload, prediksi, verifikasi |
| **Yohana Sinaga** | Frontend — konsumsi API, proxy, kontrak response |

---

> **Update README ini** di PR yang sama saat menambah/mengubah endpoint, model, migration, atau konvensi. Jangan biarkan README usang.