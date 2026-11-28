# Wildlife Tracking API

Base URL: `http://127.0.0.1:8000/api` (juga tersedia dengan prefix versi: `/api/v1/...`).
Dokumentasi interaktif (otomatis dari kode): `GET /docs/api` (Scramble, mode lokal).

## Setup

```bash
composer install
cp .env.example .env && php artisan key:generate
# isi DB_* (MySQL) dan GROQ_API_KEY di .env
php artisan migrate:fresh --seed
php artisan serve
```

Skema migrasi dirapikan ulang, jadi database lama perlu `migrate:fresh --seed`.
Akun seed (password `password123`): `admin@`, `peneliti@`, `lapangan@`, `kebijakan@`, `loket@`, `pengunjung@wildlife.id`.

## Format respons

```json
{ "success": true, "message": "OK", "data": ..., "errors": null }
```

- Endpoint list mengembalikan **array polos** di `data`. Pagination opsional lewat `?page=&per_page=`
  dengan metadata di header `X-Total-Count`, `X-Current-Page`, `X-Per-Page`, `X-Last-Page`.
- Sort: `?orderBy=<kolom>&order=asc|desc` (kolom di-whitelist per endpoint).
- Error: `401` belum login, `403` tidak punya permission, `404` data tidak ada, `409` bentrok
  relasi/duplikat, `422` validasi (`errors` berisi detail per field).
- Sesi: token dicabut jika tidak dipakai lebih dari `API_IDLE_TIMEOUT` menit (default 120 = 2 jam);
  responsnya `401` dengan pesan "Sesi Anda telah berakhir...". Setiap request yang berhasil memperpanjang
  sesi. Aktivitas terakhir dicatat di cache (`CACHE_STORE`), dengan `last_used_at` sebagai cadangan.
- Ekspor CSV: teks yang diawali `= + - @` diberi awalan `'` agar tidak dijalankan sebagai formula di Excel.
- CORS: origin frontend diatur lewat `FRONTEND_URLS` (pisahkan dengan koma).
- AI memakai model terlatih di ai-service: suara (YAMNet + classifier, `AI_AUDIO_SERVICE_URL`), gambar/video
  (MegaDetector + SpeciesNet, `AI_VISION_SERVICE_URL`), identifikasi individu (MegaDescriptor), wajah staf (YuNet + SFace)
  dan suara di dalam video. Galeri wajah staf: `GET/POST/DELETE /staff-faces` (admin). Rekap AI: `GET /ai/recap`. Bila `AI_QUEUE=true`, upload/analisis membalas
  `processing_status: "queued"`; polling `GET /ai-predictions/{id}` sampai selesai.
- Relasi dikirim dengan nama camelCase sesuai frontend (`trackingDevice`, `predictedSpecies`, `weatherData`, ...).

## Perubahan skema (hasil audit)

| Tabel | Sebelum | Sesudah |
|---|---|---|
| users | `name`, `role` (enum teks) | `user_id, role_id → roles, username, full_name, address, phone, status` |
| roles | `role_name` | `role_id, name_role` |
| permissions | `permission_name` | `permission_id, name_permission` |
| role_permissions | PK gabungan | `role_permission_id` + unique(role_id, permission_id) |
| animals | `name` | `animal_id, name_animal` (+ `distinctive_marks` untuk identifikasi AI) |
| incident_reports | `animal_code` (teks), `location` (nama zona) | `user_id, animal_id, species_id, zone_id` (FK) |
| tracking_devices | hanya GPS | `device_type` = `gps`/`camera`/`audio`, `zone_id`, `latitude`, `longitude`, `serial_number` |
| ai_media_uploads | gambar/video | + `audio`, `device_id`, `zone_id`, `latitude`, `longitude`, `captured_at`, `mime_type` |
| ai_predictions | spesies + confidence | + individu, kondisi fisik, perilaku, kategori suara, tingkat ancaman |

Semua relasi antar tabel memakai ID (FK), bukan nama.

**Alias kompatibilitas frontend** (tetap dikirim agar FE tidak perlu diubah):
`animal.name` = `name_animal`, `role.role_name` = `name_role`, `permission_name` = `name_permission`,
`incident.reported_by` = `user_id`, `species.animal_count`. Request juga menerima `name` (animals),
`role` / `role_name` (users/roles).

## Permission per role

| Permission | konservasionis | peneliti | admin | kebijakan | loket |
|---|:-:|:-:|:-:|:-:|:-:|
| view_data | ✓ | ✓ | ✓ | ✓ | |
| create_data / update_data | ✓ | ✓ | ✓ | | |
| upload_media | ✓ | ✓ | ✓ | ✓ | |
| verify_ai | ✓ | ✓ | ✓ | | |
| manage_master_data | | ✓ | ✓ | | |
| view_reports / export_data | | ✓ | ✓ | ✓ | |
| delete_data / manage_users / manage_roles | | | ✓ | | |
| manage_tourism | | | ✓ | | |
| sell_tickets | | | ✓ | | ✓ |
| validate_tickets | ✓ | | ✓ | | ✓ |

Role `pengunjung` (daftar sendiri lewat `POST /register`) hanya punya `book_tickets` dan tidak bisa membaca data konservasi.

Permission disimpan di `role_permissions` dan bisa diubah admin lewat `/roles/{id}/permissions`.
`create_data` = menambah data lapangan, `update_data` = mengubahnya (PUT insiden, keluar zona).
Role bawaan (4 role di atas) tidak bisa diganti nama atau dihapus, dan admin tidak bisa mencabut
`manage_roles` dari role-nya sendiri. User `nonaktif` langsung kehilangan akses (token ditolak `401`).

## Endpoint

Alias path: `devices` = `tracking-devices`, `incidents` = `incident-reports`,
`login` = `auth/login`, `logout` = `auth/logout`, `me` = `auth/me` = `users/me`.

### Auth & profil
| Method | Path | Keterangan |
|---|---|---|
| POST | `/login` | `{email, password}` → `{token, user}` (rate limit) |
| POST | `/logout` | cabut token aktif |
| POST | `/auth/refresh` | token baru |
| GET | `/me` | profil + role + permissions |
| PATCH | `/me` | ubah profil sendiri (tidak bisa ubah role) |
| PUT | `/me/password` | `{old_password, new_password, new_password_confirmation}` |

### Users & roles (admin)
`GET/POST /users`, `GET/PUT/DELETE /users/{id}`, `PATCH /users/{id}/status`, `GET /users/{id}/activity`,
`GET /users/{id}/tokens`, `DELETE /users/{id}/tokens/{tokenId}`,
`GET/POST /roles`, `GET/PUT/DELETE /roles/{id}`, `GET/PUT /roles/{id}/permissions`,
`POST/DELETE /roles/{id}/permissions/{permissionId}`, `GET /permissions`, `GET /permissions/{id}` (read-only),
`GET /activity-logs`, `GET /activity-logs/{id}` (read-only).

### Master data
| Resource | Endpoint |
|---|---|
| Species | `GET/POST /species`, `GET/PUT/DELETE /species/{id}` (hapus ditolak 409 jika masih punya satwa) |
| Animals | `GET/POST /animals`, `GET/PUT/DELETE /animals/{id}`, `PATCH /animals/{id}/status`, `GET /animals/{id}/track`, `/zone-visits`, `/incidents`, `/ai-observations`, `GET/POST /animals/{id}/reference-photos`, `DELETE /animals/{id}/reference-photos/{embeddingId}` (galeri re-identifikasi) |
| Devices | `GET/POST /devices` (`?device_type=gps,camera,audio`), `GET/PUT/DELETE /devices/{id}`, `PATCH/DELETE /devices/{id}/assign-animal`, `PATCH /devices/{id}/condition`, `/status`, `/telemetry`, `GET /devices/{id}/latest-reading`, `/gps-readings`, `/battery-history`, `/captures`, `POST /devices/{id}/captures` |
| Geo zones | `GET/POST /geo-zones`, `GET/PUT/DELETE /geo-zones/{id}`, `GET /geo-zones/{id}/animals` (`?history=1`), `/visits`, `/devices`, `GET /geo-zones/locate?lat=&lon=` |

### Telemetri & lapangan
| Method | Path | Keterangan |
|---|---|---|
| POST | `/gps-readings` | ingest 1 titik dari perangkat `device_type=gps`; otomatis update geofence (masuk/keluar zona) + notifikasi zona rawan; `battery_level`/`signal_strength` rendah memicu `device_alert` |
| POST | `/gps-readings/bulk` | `{readings: [...]}`; duplikat (device, waktu) dilewati |
| GET | `/gps-readings` | filter `device_id, animal_id, start_date, end_date, min_lat..max_lon, latest_only, limit` (default 2000 terbaru) |
| GET | `/gps-readings/{id}`, `/gps-readings/{id}/weather` | |
| GET/POST | `/animal-geo-zone-visits`, `PATCH /animal-geo-zone-visits/{id}/exit` | workflow kunjungan zona |
| GET/POST | `/incidents`, `GET/PUT /incidents/{id}` (PUT: `update_data`), `DELETE` (admin), `PATCH /incidents/{id}/sync-status` (admin, permission `delete_data`) | |
| GET/POST | `/incidents/{id}/media` | unggah bukti; `analyze=1` untuk langsung dianalisis AI |
| GET | `/weather-data`, `/weather-data/{id}` | |
| POST | `/weather-data/ingest`, `/weather-data/sync` | ingest manual / tarik dari Open-Meteo |
| GET/PATCH/DELETE | `/notifications`, `/notifications/{id}`, `/notifications/{id}/read`, `/notifications/read-all`, `/notifications/unread-count` | milik user aktif; tipe notifikasi dibatasi per role (no-show) |

### AI
| Method | Path | Keterangan |
|---|---|---|
| POST | `/ai/upload` (= `/media-uploads`) | multipart `file` (gambar/video/audio) + opsional `report_id, device_id, zone_id, latitude, longitude, captured_at` → `{media, prediction}` |
| POST | `/devices/{id}/captures` | ingest dari kamera jebak / perekam audio, lokasi diambil dari titik pemasangan perangkat |
| GET | `/ai-predictions` | filter `processing_status, verification_status, analysis_type, threat_level, min_threat, physical_condition, behavior, sound_category, predicted_species_id, predicted_animal_id, device_id, zone_id, ...` |
| GET | `/ai-predictions/pending-verification` | diurutkan dari ancaman tertinggi |
| GET | `/ai-predictions/{id}` | termasuk `raw_response` (kandidat individu, fitur akustik) |
| POST/PATCH | `/ai-predictions/{id}/verify` | `{verification_status, verified_species_id, verified_animal_id?}` |
| POST | `/ai-predictions/{id}/retry` | analisis ulang (baris sama) |
| GET | `/media-uploads`, `/media-uploads/{id}`, `/media-uploads/{id}/file`, `/media-uploads/{id}/predictions` | file disajikan dari private storage |
| POST | `/media-uploads/{id}/predictions` | picu analisis untuk media yang sudah ada |
| GET | `/ai/threats` | deteksi ancaman (`?min_level=medium`) |
| GET | `/ai/summary-report` | ringkasan eksekutif + statistik + `ai_insights` |
| GET | `/ai/recap` | rekap data AI per periode (`start_date`, `end_date`) |
| GET/POST/DELETE | `/staff-faces` | galeri wajah staf (biometrik, khusus `manage_users`) |
| GET | `/ai/vocabulary` | label Indonesia untuk kondisi, perilaku, kategori suara, tingkat ancaman |
| POST | `/ai/detect-objects` | analisis cepat gambar tanpa disimpan |
| GET | `/ai/health` | status engine AI |

### Laporan & ekspor
`GET /aggregate-reports`, `POST /aggregate-reports/generate` (`report_type` daily/weekly/monthly, periode opsional),
`GET /aggregate-reports/{id}`, `GET /aggregate-reports/{id}/download` (CSV), `DELETE /aggregate-reports/{id}`.

Ekspor (CSV UTF-8 dengan BOM): `/export/gps/csv`, `/export/gps/geojson`, `/export/animals/csv`, `/export/species/csv`,
`/export/devices/csv`, `/export/incidents/csv`, `/export/weather/csv`, `/export/ai-predictions/csv`,
`/export/geo-zones/csv`, `/export/geo-zones/geojson`, `/export/zone-visits/csv`, `/export/aggregate-report/{id}`,
`/export/users/csv`, `/export/activity-logs/csv` (admin). Filter umum: `start_date`, `end_date`.

### Wisata (pengunjung, tiket, tur)

Publik (tanpa login, rate limit 60/menit):
`GET /register/challenge` (soal keamanan: `challenge_token`, `question`), `POST /register` (`full_name, username, email, phone?, password, password_confirmation, captcha_token, captcha_answer`, `website` harus kosong; role selalu `pengunjung`, 5/menit),
`GET /tourism/routes`, `GET /tourism/routes/{slug}` (rute + 30 shift terdekat + ulasan),
`GET /tourism/guides`, `GET /tourism/guides/{id}` (+ jadwal), `GET /tourism/guides/{id}/photo`,
`GET /tourism/schedule?from=&to=&route_id=&guide_id=` (maks. 62 hari; tiap shift punya `remaining`),
`GET /tourism/prices`, `POST /tourism/quote` (`route_id, visit_date, wni_count, wna_count, student_count, drone_count`),
`GET /tourism/payment-info`.

Pengunjung (`book_tickets`):

| Method | Path | Keterangan |
|---|---|---|
| GET | `/visitor/bookings` | Pemesanan milik sendiri |
| POST | `/visitor/bookings` | `route_id, shift_id, wni_count, wna_count, student_count, drone_count?, contact_name, contact_phone, emergency_contact, notes?, payment_method (bank_transfer/qris/ewallet), participants[{full_name, visitor_type wni/wna/pelajar, age?}]`. `409` bila kuota habis |
| GET | `/visitor/bookings/{code}` | Detail + `payment_window_seconds_left` |
| POST | `/visitor/bookings/{code}/cancel` | Hanya sebelum lunas |
| POST | `/visitor/bookings/{code}/payment-proof` | Multipart `file` (jpg/png/webp/pdf, maks. 5 MB) |
| GET | `/visitor/bookings/{code}/ticket` | `qr_payload` setelah lunas (`409` sebelumnya) |
| POST | `/visitor/bookings/{code}/pay-online` | Midtrans Snap → `redirect_url` (503 bila `MIDTRANS_SERVER_KEY` kosong) |
| POST | `/visitor/bookings/{code}/simulate-payment` | Simulator gateway gratis (`channel`: `qris\|va_bca\|va_bni\|va_bri\|va_mandiri\|gopay\|ovo\|dana\|shopeepay`, `outcome?`: `paid\|failed`) → verifikasi otomatis & terbit tiket (503 bila `PAYMENT_SIMULATOR_ENABLED=false`) |
| POST | `/visitor/bookings/{code}/review` | `route_rating 1-5, guide_rating?, comment?` setelah check-in, sekali |

Status pemesanan: `pending_payment` → `awaiting_verification` → `paid` → `checked_in` → `completed`
(atau `cancelled` / `expired`). Pemesanan orang lain selalu `404`.

Petugas loket (`validate_tickets`): `POST /tourism/check-in` (`ticket` = isi QR `KODE.TOKEN`), `GET /tourism/check-in/today`.

Pengelola (`manage_tourism`), prefix `/tourism/admin`: `routes` (GET/POST, `{id}` GET/PUT/DELETE),
`guides` (GET/POST, `{id}` PUT/DELETE, `{id}/photo` POST), `shifts` (GET/POST, `{id}` PUT/DELETE),
`prices` (GET, `{id}` PUT), `holidays` (GET/POST, `{id}` DELETE), `bookings` (GET, `{code}` GET, `{code}/cancel` POST),
`payments` (GET, `{id}/proof` GET, `{id}/verify` POST `{approve, reason?}`), `reviews` (GET, `{id}` PATCH `{is_visible}`).

Webhook gateway: `POST /payments/webhook`, header `X-Signature: hex(HMAC-SHA256(body, PAYMENT_WEBHOOK_SECRET))`,
body `{reference, status: paid|failed, amount, transaction_id}`.

Notifikasi Midtrans: `POST /payments/midtrans/notification` (body notifikasi standar Midtrans; `signature_key` = SHA512(order_id+status_code+gross_amount+server_key); status dicek ulang ke API Midtrans).

### Ensiklopedia satwa 3D

Publik (tanpa login, 60/menit): `GET /wildlife/species?search=&taxon_class=&conservation_status=`, `GET /wildlife/species/{id}`,
`GET /wildlife/species/{id}/model` (`model/gltf-binary`). Hanya spesies `is_public=true`; tanpa data individu/lokasi.

Staf: `GET /species/{id}/model` (`view_data`). `manage_master_data`: `PUT /species/{id}` (profil: `local_names, taxon_class, taxon_order, taxon_family, description, characteristics[], size_weight, lifespan, activity_pattern, diet, behavior, habitat, distribution, population_estimate, threats[], fun_facts[], legal_protection, is_public, model_hotspots[{label, description?, position "Xm Ym Zm", normal?}]`),
`POST /species/{id}/model` (multipart `file` .glb ≤ 30 MB, `model_source` scan/licensed/illustrative, `model_credit`), `DELETE /species/{id}/model`.

### Token perangkat

`manage_master_data`: `GET /devices/{id}/api-token` (status), `POST /devices/{id}/api-token` (token baru, ditampilkan sekali; token lama dicabut), `DELETE /devices/{id}/api-token`.
Token hanya untuk `PATCH /devices/{id}/telemetry`, `POST /devices/{id}/captures`, `POST /gps-readings` dan `/gps-readings/bulk` milik perangkat itu; tanpa timeout idle.

### POS loket (walk-in)

Kasir (`sell_tickets`; role `petugas_loket` dan admin). Kasir hanya melihat sesi & transaksinya sendiri.

| Method | Path | Keterangan |
|---|---|---|
| GET | `/pos/session` | Sesi terbuka milik kasir (`null` bila belum buka) + `expected_cash_now` + `summary` |
| POST | `/pos/session/open` | `counter_name, opening_cash`. `409` bila masih ada sesi terbuka |
| POST | `/pos/session/close` | `counted_cash, note?` → `expected_cash`, `cash_difference` (counted − expected) |
| GET | `/pos/sessions?date=&cashier_id=` | Riwayat sesi (admin: semua kasir) |
| GET | `/pos/sessions/{id}` | Laporan sesi + daftar `sales` |
| GET | `/pos/sales` | Transaksi sesi yang sedang terbuka |
| POST | `/pos/sales` | `route_id, shift_id, wni_count, wna_count, student_count, drone_count?, contact_name, contact_phone?, emergency_contact?, notes?, participants?[{full_name?, visitor_type, age?}], payment_method (cash/qris/edc), received_amount (wajib untuk cash), external_reference?, check_in_now?`. Respons memuat `total_amount`, `payments[0].change_amount`, `qr_payload`. `409` tanpa sesi / kuota habis; `422` uang kurang / shift sudah lewat 30 menit |
| GET | `/pos/sales/{code}` | Data struk + `qr_payload` (cetak ulang) |
| POST | `/pos/sales/{code}/void` | `reason` (min. 5). Hanya kasir penjual & sesi terbuka |

Laporan harian (`manage_tourism`): `GET /pos/report?date=YYYY-MM-DD` → `summary{transactions, voided, visitors, gross, pnbp, guide_fee, by_method, by_channel}` + `sessions`. Dihitung per tanggal pembayaran (zona `TOURISM_TIMEZONE`).

## Pipeline AI

```
kamera / perekam audio / unggahan petugas
  → simpan media (private storage) + lokasi (input atau titik pemasangan perangkat) + zona
  → analisis
       gambar : spesies, jumlah individu, kondisi fisik, perilaku, ciri pembeda, aktivitas manusia (senjata, jerat, penebangan, api)
       video  : frame diambil dengan ffmpeg lalu dianalisis seperti gambar
       audio  : kategori suara (satwa, tembakan, gergaji mesin, pohon tumbang, mesin, kendaraan, suara manusia, ...)
  → identifikasi individu: skor kandidat dari jarak GPS kalung saat waktu rekam, kehadiran di zona
    yang sama (animal_geo_zones), kecocokan ciri fisik (animals.distinctive_marks), dan apakah hanya ada satu individu spesies tsb.
  → tingkat ancaman → insiden otomatis (perburuan / perambahan / kebakaran_hutan / kesehatan_satwa)
    + notifikasi risk_zone ke petugas lapangan & admin
  → verifikasi petugas
```

Engine:
- Gambar & teks: Groq (`GROQ_VISION_MODEL`, `GROQ_TEXT_MODEL`).
- Audio: urutan prioritas: (1) microservice `AI_AUDIO_SERVICE_URL` (mis. YAMNet/BirdNET),
  kontrak `POST {url}/classify-audio` multipart `file` → `{label, confidence, category?, species?}`;
  (2) Groq Whisper + LLM dengan fitur akustik; (3) heuristik fitur akustik WAV (fallback offline, perlu verifikasi petugas).
- Bila engine tidak tersedia, upload tetap tersimpan dengan `processing_status = failed` dan bisa di-`retry`.
