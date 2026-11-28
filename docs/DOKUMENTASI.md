# Dokumentasi Backend Wildlife Tracking

Diperbarui: 2026-11-26 · Laravel 13 + Sanctum · 249 endpoint REST (juga di `/api/v1`) · 130 test lulus.

Dokumen ini adalah rujukan utama untuk tim frontend dan backend: fitur yang sudah siap, cara memanggilnya,
apa yang berubah di setiap rilis, fitur yang masih dikembangkan, dan hasil audit terakhir.
Daftar endpoint ringkas ada di [`API.md`](API.md); dokumentasi interaktif di `GET /docs/api` (mode lokal).
Menjadikan PC sebagai server tim: [`DEPLOY.md`](DEPLOY.md). Layanan AI suara: [`../ai-service/README.md`](../ai-service/README.md).

Isi:

1. [Cara memanggil API](#1-cara-memanggil-api)
2. [Apa yang diubah](#2-apa-yang-diubah)
3. [Hak akses per role](#3-hak-akses-per-role)
4. [Fitur siap pakai](#4-fitur-siap-pakai)
5. [Fitur AI](#5-fitur-ai)
6. [Fitur yang masih akan dikembangkan](#6-fitur-yang-masih-akan-dikembangkan)
7. [Hasil audit](#7-hasil-audit)
8. [Setup dan update](#8-setup-dan-update)

---

## 1. Cara memanggil API

| Hal | Nilai |
|---|---|
| Base URL | `http://127.0.0.1:8000/api` (juga `/api/v1/...`) |
| Autentikasi | Header `Authorization: Bearer <token>` dari `POST /login` |
| Header wajib | `Accept: application/json`; `Content-Type: application/json` kecuali upload file (multipart) |
| Sesi | Token dicabut jika tidak dipakai lebih dari 2 jam (`API_IDLE_TIMEOUT`, menit). Respons `401` |
| CORS | Origin frontend diatur lewat `FRONTEND_URLS` (default `http://localhost:3000,http://127.0.0.1:3000`) |
| Akun uji | `admin@`, `peneliti@`, `lapangan@`, `kebijakan@`, `loket@`, `pengunjung@wildlife.id`, password `password123` |

Format respons selalu sama:

```json
{ "success": true, "message": "OK", "data": ..., "errors": null }
```

- List mengembalikan array polos di `data`. Pagination opsional `?page=1&per_page=25` (maks. 200);
  total ada di header `X-Total-Count`, `X-Current-Page`, `X-Per-Page`, `X-Last-Page`.
- Sort opsional `?orderBy=<kolom>&order=asc|desc` (kolom dibatasi per endpoint).
- Error: `401` belum login / sesi habis / akun nonaktif, `403` tidak punya izin, `404` data tidak ada,
  `409` bentrok relasi/duplikat, `422` validasi (detail per field di `errors`), `429` terlalu banyak percobaan.
- Relasi dikirim camelCase: `trackingDevice`, `predictedSpecies`, `predictedAnimal`, `verifiedSpecies`, `weatherData`.

Login dari frontend (sesuai `laravel-auth.service.ts`):

```ts
const res = await fetch(`${API_URL}/login`, {
  method: "POST",
  headers: { Accept: "application/json", "Content-Type": "application/json" },
  body: JSON.stringify({ email, password }),
});
const { data } = await res.json(); // data.token, data.user.role.role_name, data.user.permissions
```

Setiap respons `401` berarti token sudah tidak berlaku (sesi habis, logout, akun dinonaktifkan, atau
password direset admin). Frontend cukup menghapus token lalu mengarahkan ke halaman login:

```ts
if (res.status === 401) {
  clearToken();            // fungsi penyimpanan token milik FE
  router.push("/login");   // message: "Sesi Anda telah berakhir ..." atau "Unauthenticated ..."
}
```

Alias agar frontend tidak perlu diubah — backend tetap menerima dan mengirim nama lama:

| Dipakai frontend | Kolom asli |
|---|---|
| `animal.name` (baca & kirim) | `name_animal` |
| `role.role_name` | `name_role` |
| `permission_name` | `name_permission` |
| `incident.reported_by` | `user_id` |
| `species.animal_count` | jumlah satwa per spesies |
| `role` (nama) saat membuat user | dikonversi ke `role_id` |
| path `/devices`, `/incidents`, `/login`, `/me` | juga `/tracking-devices`, `/incident-reports`, `/auth/login`, `/auth/me` |

---

## 2. Apa yang diubah

Riwayat terbaru di atas. Setiap rilis wajib diikuti `php artisan migrate:fresh --seed` di database lokal/server.

### Notifikasi per role dengan pembatasan ketat / no-show (2026-11-26)

- **Visibilitas per role (`NotificationService`, `NotificationController`)**: seluruh 6 role dapat mengakses `/notifications`, namun tipe yang dikirim dan ditampilkan dibatasi ketat:
  - `administrator_sistem`, `konservasionis_lapangan`, `peneliti_ekologi`: `risk_zone`, `device_alert`, `system` (+ relasi konservasi).
  - `pengambil_kebijakan`: `risk_zone`, `system`.
  - `petugas_loket`, `pengunjung`: hanya `system` (tanpa relasi konservasi; tipe di luar daftar tidak pernah dibuat dan disembunyikan dengan `404` no-show).
- **Test**: `NotificationRoleVisibilityTest` (3 skenario).

### Simulator payment gateway otomatis (2026-11-26)

- **Endpoint baru `POST /visitor/bookings/{code}/simulate-payment`** (`permission:book_tickets`, rate limit 20/menit): mensimulasikan pembayaran QRIS, Virtual Account (`va_bca`, `va_bni`, `va_bri`, `va_mandiri`), dan E-Wallet (`gopay`, `ovo`, `dana`, `shopeepay`) dengan `outcome` `paid` atau `failed`. Ketika `paid`, backend memanggil `BookingService::markPaid` secara atomik sehingga status pemesanan langsung menjadi `paid` dan e-tiket QR terbit otomatis tanpa verifikasi manual petugas.
- **Konfigurasi**: `PAYMENT_SIMULATOR_ENABLED` di `.env` (default `false` di `config/services.php`, `true` di `.env.example` untuk demo/pengembangan). `GET /tourism/payment-info` mengembalikan `simulator_enabled`, dan `php artisan security:check` memberi peringatan bila simulator masih aktif di lingkungan produksi.
- **Test**: `PaymentSimulatorTest` (3 skenario: nonaktif 503, simulasi berhasil + isolasi pemilik/IDOR, dan simulasi gagal lalu ulang bayar).

### Pembaruan panduan Apidog, konsistensi dokumentasi & audit dependensi (2026-11-26)

- **`test.md` (Apidog)** diperbarui agar selaras dengan `docs/API.md` dan `routes/api.php` (249 endpoint, 6 akun uji termasuk `loket@` dan `pengunjung@wildlife.id`, token perangkat, wisata, POS loket, ensiklopedia satwa 3D, Midtrans Snap, CAPTCHA daftar).
- **`docs/API.md`** dilengkapi dengan akun seed `loket@` & `pengunjung@wildlife.id`, baris role `loket` (`sell_tickets`, `validate_tickets`) di tabel permission, serta endpoint `GET /ai/recap` dan `/staff-faces`.
- **`.gitignore`** menambahkan `AGENTS.md` agar berkas asisten lokal tidak pernah ter-commit.
- **Audit**: `phpunit` 130/130 lulus (922 assertion), `security:check` bersih untuk konfigurasi (password seed `password123` wajib direset di server via `users:reset-default-passwords`), `composer audit` menemukan 8 advisory pada 5 paket (`laravel/framework`, `symfony/http-foundation`, `symfony/http-kernel`, `symfony/mime`, `symfony/routing`) untuk ditinjau pada siklus pembaruan paket berikutnya.

### Ensiklopedia satwa 3D, pembayaran online, token perangkat & penutupan risiko (2026-11-26)

**Ensiklopedia satwa 3D** (untuk peneliti, konservasionis, pengunjung, dan petugas loket):

| Area | Isi | Endpoint |
|---|---|---|
| Profil spesies | Kolom baru: nama daerah, taksonomi, deskripsi, ciri-ciri, ukuran & berat, umur, pola aktivitas, makanan, perilaku, habitat, sebaran (tingkat wilayah), estimasi populasi, ancaman, fakta menarik, status perlindungan (Permen LHK P.106/2018 & CITES), `is_public`. 8 satwa terisi: orangutan, harimau, badak, gajah Sumatra, rusa sambar, siamang, rangkong badak, beruang madu | `GET /wildlife/species`, `GET /wildlife/species/{id}` (publik, tanpa login) |
| Model 3D | GLB per spesies + titik keterangan (hotspot) di permukaan model. 8 model **ilustratif** (low-poly, dibuat `tools/species-models/generate.py`) dengan 3 titik ciri khas masing-masing | `GET /wildlife/species/{id}/model` (publik), `GET /species/{id}/model` (staf) |
| Kelola (`manage_master_data`) | Unggah/ganti model `.glb` (maks. 30 MB) dengan sumber (`scan`/`licensed`/`illustrative`) & kredit lisensi; hapus model; ubah profil & titik keterangan | `POST/DELETE /species/{id}/model`, `PUT /species/{id}` |
| Keamanan | Katalog publik **tidak** memuat data individu, GPS, zona, atau jumlah individu terpantau (risiko perburuan). GLB divalidasi: header glTF 2.0, panjang sesuai, JSON valid, **tanpa URI eksternal**. Semua teks profil dibuang tag HTML-nya. Hotspot dibatasi format koordinat | `WildlifeEncyclopediaTest` (5) |

**Pembayaran online Midtrans Snap**: `POST /visitor/bookings/{code}/pay-online` mengembalikan URL halaman Midtrans (QRIS, VA, e-wallet, kartu); notifikasi di `POST /payments/midtrans/notification` diverifikasi `signature_key` SHA-512, lalu status **diambil ulang dari API Midtrans**, nominal dicocokkan, idempoten. Aktif bila `MIDTRANS_SERVER_KEY` diisi; tagihan manual lain otomatis kedaluwarsa saat lunas. Test: `MidtransTest` (4, respons Midtrans tiruan).

**Token perangkat**: `GET/POST/DELETE /devices/{id}/api-token` (`manage_master_data`). Token ber-ability `device:{id}` milik akun sistem `sistem-perangkat`, tidak kedaluwarsa karena idle, ditampilkan sekali, dan hanya boleh mengirim telemetri/rekaman/GPS perangkat itu (middleware `RestrictDeviceTokens`).

**Penutupan risiko lain**:

- CAPTCHA pendaftaran (`GET /register/challenge`) + honeypot `website`.
- `TRUSTED_PROXIES` untuk IP pengunjung asli.
- Validasi insiden & perangkat.
- Perintah `security:check`, `users:reset-default-passwords`, dan `tokens:prune-idle` (terjadwal tiap jam).
- Libur nasional 2027 dari SKB 3 Menteri.

Lihat tabel "Sudah diperbaiki" #18–#27.

Migrasi baru: `2026_11_26_000001_add_profile_and_model_to_species`. Variabel `.env` baru: `TRUSTED_PROXIES`, `REGISTRATION_CAPTCHA`, `REGISTRATION_CAPTCHA_MIN_SECONDS`, `MIDTRANS_SERVER_KEY`, `MIDTRANS_IS_PRODUCTION`, `MIDTRANS_FINISH_URL`. Test: 130.

### POS loket wisata & perbaikan zona waktu (2026-11-25)

Penjualan tiket walk-in di loket (Point of Sales) untuk pengunjung yang datang langsung tanpa pesan online.

| Area | Isi | Endpoint |
|---|---|---|
| Role & izin baru | Role `petugas_loket` (`sell_tickets` + `validate_tickets`), akun contoh `loket@wildlife.id`. Admin otomatis punya `sell_tickets` | - |
| Sesi kas | Kasir membuka sesi (nama loket + modal kembalian), satu sesi terbuka per kasir. Saat tutup, sistem menghitung kas seharusnya (modal + tunai sah) dan mencatat selisih dengan hitungan fisik | `GET /pos/session`, `POST /pos/session/open`, `POST /pos/session/close`, `GET /pos/sessions`, `GET /pos/sessions/{id}` |
| Penjualan | Pilih rute + shift (hari ini atau nanti), jumlah WNI/WNA/pelajar/drone. Harga dihitung server (sama dengan online, termasuk tarif libur). Bayar tunai (uang diterima → kembalian), QRIS, atau EDC (kode approval). Langsung lunas, tiket QR terbit, opsi langsung check-in. Nama peserta boleh dilengkapi belakangan (otomatis "Peserta n") | `POST /pos/sales`, `GET /pos/sales`, `GET /pos/sales/{code}` |
| Kuota | Kuota shift dipakai bersama dengan pemesanan online dan dikunci di transaksi. Shift yang sudah mulai masih bisa dijual sampai 30 menit setelah jam mulai | - |
| Void | Kasir yang sama, selama sesi masih terbuka, wajib alasan. Tiket langsung tidak berlaku. Setelah sesi ditutup hanya admin yang bisa membatalkan (refund) | `POST /pos/sales/{code}/void` |
| Laporan harian | Gabungan online + loket per tanggal pembayaran (WIB): jumlah transaksi & pengunjung per kategori, per metode bayar, per kanal, dan **PNBP dipisah dari jasa pemandu** (PNBP wajib disetor ke kas negara) | `GET /pos/report?date=` (`manage_tourism`) |

Perbaikan dalam rilis ini:

- **Zona waktu wisata.** `APP_TIMEZONE` adalah UTC, sedangkan jam shift ditulis dalam WIB. Akibatnya batas "pemesanan ditutup 2 jam sebelum shift", check-in "hanya di tanggal kunjungan", dan daftar kunjungan hari ini meleset 7 jam. Sekarang semua perhitungan wisata memakai `TourismClock`, dengan zona waktu dari `TOURISM_TIMEZONE` (default `Asia/Jakarta`). Data GPS dan log tetap UTC.
- **Rate limit.** Semua route `throttle:N,1` tanpa prefix ternyata berbagi satu penghitung per user atau IP. Contohnya, 6× membuka katalog lalu daftar akun langsung `429`, dan penjualan kasir menghabiskan jatah buka/tutup sesi. Setiap grup sekarang punya prefix sendiri, misalnya `throttle:5,1,register`.
- Seeder membuat shift mulai hari ini agar loket bisa langsung dicoba.
- Pembeli walk-in tidak punya akun, sehingga `bookings.user_id` sekarang boleh kosong. Transaksi seperti ini tidak muncul di "Pesanan Saya" siapa pun dan tidak bisa diulas.

Migrasi baru `2026_11_25_000001_add_pos_to_tourism`:

- Tabel baru `pos_sessions`.
- `bookings.channel`, `pos_session_id`, `sold_by`.
- `payments.method` bertambah `cash` dan `edc`.
- `payments.received_amount`, `change_amount`, `external_reference`.

Rollback migrasi ini ditolak bila sudah ada transaksi POS, supaya data penjualan tidak hilang. Variabel `.env` baru (opsional): `TOURISM_TIMEZONE`. Test baru: `PosTest` (8).

### Modul wisata: pengunjung, tiket, tur, pemandu, transaksi (2026-11-24)

Pengunjung bisa mendaftar, melihat paket tur beserta detailnya, melihat jadwal & profil pemandu per shift,
memesan, membayar, menerima tiket QR, check-in di pintu masuk, dan menulis ulasan. Petugas mengelola semuanya.

| Area | Isi | Endpoint |
|---|---|---|
| Role baru | `pengunjung` (izin `book_tickets` saja); izin staf baru `manage_tourism` (admin) dan `validate_tickets` (lapangan, admin) | `POST /register` |
| Tarif | PP 36/2024 (PNBP KLHK, berlaku 30-10-2024), TNGL Kelas III: WNI Rp10.000 Senin-Sabtu / Rp15.000 Minggu & libur; pelajar (min. 5) Rp5.000 / Rp7.500; WNA Rp150.000; drone Rp2.000.000/unit/hari. Bisa diubah admin; pemesanan lama memakai harga saat dipesan | `GET /tourism/prices`, `PUT /tourism/admin/prices/{id}` |
| Paket tur | 4 rute contoh (Bukit Lawang 1 hari & 2H1M, Tangkahan, Ketambe): itinerary per jam, titik kumpul + koordinat, durasi, jarak, tingkat kesulitan, usia minimum, yang termasuk, kelengkapan yang dibawa, aturan, satwa yang mungkin terlihat, titik jalur (untuk peta/rute 3D) | `GET /tourism/routes`, `/tourism/routes/{slug}` |
| Pemandu & shift | Profil (bio, bahasa, keahlian, hewan favorit + alasan, pengalaman, sertifikasi, fakta unik, rating) dan jadwal shift pagi/siang/malam per rute dengan sisa kuota | `GET /tourism/guides`, `/tourism/guides/{id}`, `/tourism/schedule` |
| Pemesanan | Harga dihitung server; kuota shift dikunci di transaksi (tidak bisa overbooking); batas bayar 60 menit lalu kuota dilepas | `POST /tourism/quote`, `/visitor/bookings` |
| Transaksi | Bukti transfer/QRIS diunggah pengunjung lalu diverifikasi petugas, atau webhook payment gateway bertanda tangan HMAC; pembatalan lunas hanya oleh petugas (status `refunded`) | `/visitor/bookings/{code}/payment-proof`, `/tourism/admin/payments/{id}/verify`, `POST /payments/webhook` |
| Tiket & check-in | Tiket QR `KODE.TOKEN` (token HMAC dari `APP_KEY`), sekali pakai, hanya di tanggal kunjungan | `/visitor/bookings/{code}/ticket`, `POST /tourism/check-in` |
| Ulasan | Setelah check-in, satu per pemesanan; tag HTML dibuang; admin bisa menyembunyikan | `/visitor/bookings/{code}/review`, `PATCH /tourism/admin/reviews/{id}` |

Keamanan: lihat bagian 7 ("Modul wisata"). Migrasi baru `2026_11_24_000001_create_tourism_tables`, seeder
`TourismSeeder`, akun contoh `pengunjung@wildlife.id` / `password123`. Variabel `.env` baru: `TOURISM_*`,
`PAYMENT_WEBHOOK_SECRET`. Jalankan `php artisan schedule:work` (atau cron) agar pemesanan tak dibayar
dilepas otomatis; tanpa itu kuota tetap dilepas saat ada yang membuka jadwal/pemesanan. Test baru: `TourismTest` (16).

### Dokumentasi arsitektur untuk anggota baru (2026-11-23)

[`ARSITEKTUR.md`](ARSITEKTUR.md): struktur folder, fungsi tiap file, database & relasi, `.env`, alur request, hubungan FE–BE, dan cara kerja AI. Tidak ada perubahan kode, API, atau database.

### Perbaikan deteksi manusia di ai-service (2026-11-23)

| Fitur | Sebelum | Sesudah | Dampak ke FE |
|---|---|---|---|
| Deteksi orang di foto/video | Detektor SpeciesNet memberi label `human`, sedangkan ai-service dan Laravel menghitung `person`: `person_count` selalu 0, kotak orang berlabel "satwa", dan orang yang wajahnya tidak terlihat tidak dihitung sebagai aktivitas manusia | Label dinormalisasi ke `person`; kotak berlabel "manusia"; orang tanpa wajah terlihat ikut dihitung | `detected_objects[].label` kini "manusia" untuk orang; ancaman aktivitas manusia lebih tepat |

Diuji ulang: foto staf → 1 manusia, Farrellino dikenali, ancaman `none`; foto orang asing → 2 manusia, 2 wajah tidak dikenal, ancaman `medium`.
Server tim perlu menjalankan ulang jendela ai-service (tidak perlu setup ulang).

### Fallback bila ai-service mati (2026-11-23)

| Fitur | Sebelum | Sesudah | Dampak ke FE |
|---|---|---|---|
| Upload gambar/video saat ai-service (port 8001) belum jalan | Langsung `processing_status: failed` dengan pesan koneksi mentah | Gambar dianalisis Groq sebagai cadangan (`completed`, tanpa wajah staf/re-ID). Bila Groq juga tidak diisi, `ai_summary` menjelaskan: "ai-service tidak bisa dihubungi ... Jalankan uvicorn di port 8001." | Tidak ada; respons sama |
| `model_version` saat fallback | Tetap `speciesnet-v4.0.3a+groq:…` walau SpeciesNet tidak dipakai | `groq:<model>` (ditambah `+yamnet` bila ada audio video) | Bisa dipakai FE untuk menandai "hasil cadangan" |
| `scripts/setup-ai-service.ps1` | Cek akhir hanya audio, vision, reid | Cek akhir juga memuat `faces` | Tidak ada |

Error ai-service selain koneksi (mis. HTTP 503, file rusak) tetap menandai prediksi `failed` agar bisa di-retry.

### Rilis pengenalan wajah staf, audio pada video, rekap data AI (2026-11-23)

| Area | Sebelum | Sesudah | Dampak ke FE |
|---|---|---|---|
| Wajah manusia | Hanya "ada manusia" | Wajah di gambar & video dicocokkan dengan **galeri wajah staf** (YuNet + SFace, OpenCV): nama staf, "Kemungkinan <nama> (perlu verifikasi)", atau **"Tidak dikenal / pengunjung"** | Field baru di prediksi: `faces`, `staff_face_count`, `unknown_face_count` |
| Ancaman manusia | Setiap orang terdeteksi = aktivitas manusia | Hanya orang/wajah yang **bukan staf**; staf terdaftar tidak memicu ancaman | Notifikasi lebih sedikit untuk patroli staf |
| Audio pada video | Video hanya dianalisis gambarnya | Trek suara video ikut dianalisis (tembakan, gergaji, ...); ancaman & insiden mengikuti suara | `sound_category`, `sound_label` terisi untuk video; `model_version` berakhiran `+yamnet` |
| Tembakan beruntun | Terbaca "suara manusia" | Ditandai **kemungkinan tembakan beruntun** (`medium`, notifikasi tanpa insiden otomatis) | Label suara memuat teks itu |
| Summary report | Statistik umum | **`ai_recap`** di `/ai/summary-report` + endpoint **`GET /ai/recap`**: total, spesies, individu, wajah staf/tidak dikenal, suara (termasuk dari video), ancaman terbaru, per zona, deret harian | Data siap untuk tampilan & ekspor PDF di FE |
| Galeri wajah staf | – | `GET/POST/DELETE /staff-faces`, `GET /staff-faces/{id}/file`, perintah `php artisan staff:import-faces <folder>` | Halaman admin baru (opsional) |

Hasil uji (detail: `ai-service/training/README.md`): ambang 0,40 → staf dikenali 99,7% (simulasi LFW), orang lain lolos
sebagai staf 0,32%; dua staf asli 16/19 foto dikenali (leave-one-out), 0 tertukar, 1/2.500 orang lain lolos.
Migrasi baru `2026_11_23_000001_create_staff_faces_and_face_results`. Variabel baru: `AI_FACE_THRESHOLD`,
`AI_FACE_POSSIBLE_THRESHOLD`. Test baru: `FaceRecognitionAndRecapTest` (10 test).

### Setup ai-service sekali jalan (2026-11-23)

`scripts/setup-ai-service.ps1` memastikan Python 3.12, membuat `ai-service\.venv`, memasang paket yang kurang, mengunduh
model yang belum ada, lalu mengecek keempat model (audio, vision, reid, faces) bisa dimuat. `reverse_geocoder` (dependensi
SpeciesNet yang terlewat di Windows) kini tercantum di `requirements.txt`, dan `download_model.py` tetap mengunduh model
lain bila satu gagal lalu menampilkan ringkasan OK/GAGAL. Tidak ada perubahan API atau database.

### Perbaikan skrip server tim (2026-11-23)

`scripts/start-server.ps1` sekarang menemukan `cloudflared` yang dipasang winget di `Program Files` walau tidak ada di PATH
(sebelumnya skrip berhenti dengan "'cloudflared' tidak ditemukan"). Tidak ada perubahan API atau database.

### Rilis model AI terlatih: suara, kamera jebak, identifikasi individu (2026-11-22)

Semua fitur AI utama kini memakai model yang **sudah terlatih dan teruji pada data nyata**, bukan tebakan LLM.
Detail data, metode, dan angka uji: [`ai-service/training/README.md`](../ai-service/training/README.md).

| Area | Sebelum | Sesudah | Hasil uji (data yang tidak dipakai latihan) |
|---|---|---|---|
| Klasifikasi suara | Aturan zero-shot YAMNet | YAMNet + **classifier yang dilatih proyek ini** (ESC-50, rekaman hutan Vietnam, Lund) | Akurasi 96,1% (2.548 klip) |
| Tembakan | Zero-shot: 11% tertangkap di hutan, petir 72% terbaca tembakan | **Detektor tembakan terlatih** (pita 0–4 kHz) + veto suara alam/manusia | 85% tertangkap, alarm palsu 1,3%; 97% pada 10–350 m (Lund) |
| Deteksi satwa/manusia/kendaraan + jumlah individu | Tebakan Groq vision | **MegaDetector v5a** | 79/90 (88%) foto bersatwa terdeteksi, 0/20 foto kosong salah |
| Spesies | Tebakan Groq vision | **SpeciesNet v4.0.3a** (Google), geofence Indonesia | Sambar 14/15, babi hutan 13/15, kijang 17/20 (foto Laos/Vietnam) |
| Identifikasi individu | GPS/zona/kata ciri fisik | + **re-identifikasi penampilan MegaDescriptor** dengan galeri foto per satwa | Top-1 97,9% (107 harimau); individu baru 86,5% tidak dipaksa cocok |
| Video | 1 frame | 1 frame tiap 2 detik (maks. 8), hasil paling spesifik dipakai | – |
| Kondisi fisik, perilaku, senjata/jerat/penebangan/api | Groq vision | Tetap Groq vision, kini dengan konteks hasil deteksi (belum ada model terlatih publik) | Belum diukur |

Endpoint baru: `GET/POST /animals/{id}/reference-photos`, `GET /animals/{id}/reference-photos/{embeddingId}/file`,
`DELETE /animals/{id}/reference-photos/{embeddingId}`. Verifikasi prediksi dengan `verified_animal_id` otomatis
menambah foto ke galeri individu. `animal_match_method` bisa bernilai `appearance_reid`.

Perbaikan ikut ditemukan saat pengujian: MP4 yang dikenali sistem sebagai `application/mp4` dulu ditolak (kini
diterima); berkas yang isinya bukan gambar/video/audio dulu `500` (kini `422`); urutan kandidat individu tidak lagi
terpotong batas 0,99.

Migrasi baru: `2026_11_22_000002_create_animal_embeddings_table`. Variabel baru: `AI_VISION_SERVICE_URL`,
`AI_COUNTRY`, `AI_REID_THRESHOLD`. Test baru: `VisionModelsIntegrationTest` (11 test).

### Rilis AI suara nyata (YAMNet) & antrean AI (2026-11-22)

| Area | Sebelum | Sesudah | Dampak ke FE |
|---|---|---|---|
| Klasifikasi suara | Groq Whisper + LLM, atau aturan WAV (akurasi rendah) | **ai-service YAMNet** (gratis, CPU, ~0,2 detik/rekaman) lewat `AI_AUDIO_SERVICE_URL`; Groq/heuristik tetap jadi cadangan bila ai-service mati | Tidak ada; `model_version` menjadi `service:yamnet-audioset-v1` |
| Antrean analisis | Analisis selalu langsung saat upload | `AI_QUEUE=true` (opsional): upload langsung dibalas `processing_status: "queued"`, worker `queue:work` mengisi hasilnya | Bila diaktifkan, FE polling `GET /ai-predictions/{id}` |
| Tabel antrean | Tidak ada (antrean `database` tidak bisa dipakai) | Migrasi baru `2026_11_22_000001_create_jobs_table` (`jobs`, `job_batches`, `failed_jobs`) | — |
| `GET /ai/health` | `audio_service: configured` | Mengecek ai-service sungguhan (`reachable`, `model`) dan status antrean | Field baru `queue` |
| Retry/antrean berganti model | Bisa bentrok unique (`media_id`, `model_version`) | Baris prediksi dipakai ulang, tidak ada duplikat | — |
| Server tim | Tiap orang setup sendiri | `scripts/start-server.ps1` + Cloudflare Tunnel, panduan di `docs/DEPLOY.md` | FE cukup mengganti `API_URL` |

Variabel baru di `.env`: `AI_AUDIO_SERVICE_URL`, `AI_AUDIO_SERVICE_MODEL`, `AI_AUDIO_SERVICE_TOKEN`, `AI_QUEUE`.
Test baru: `tests/Feature/AiServiceIntegrationTest.php` (6 test) + uji end-to-end dengan YAMNet asli.

### Rilis audit keamanan & logika (2026-11-22)

| Area | Sebelum | Sesudah | Dampak ke FE |
|---|---|---|---|
| Nonaktifkan user lewat `PUT /users/{id}` | Token user tetap aktif | Semua token dicabut; token milik user `nonaktif` selalu ditolak | User nonaktif mendapat `401` |
| Admin menonaktifkan diri lewat `PUT /users/{id}` | Bisa | Ditolak `422` | — |
| Admin mereset password user | Sesi lama user tetap jalan | Semua token user itu dicabut | User harus login ulang |
| `POST /media-uploads/{id}/predictions` | Bisa menimpa prediksi yang sudah diverifikasi (status kembali `pending`) | Ditolak `409` bila media punya prediksi terverifikasi | Tampilkan pesan `409` |
| Role bawaan | Bisa diganti nama/dihapus; admin bisa mencabut `manage_roles` dari role sendiri | Nama role bawaan dikunci (`422`), hapus ditolak (`409`), admin tidak bisa mengunci dirinya (`422`) | Deskripsi role tetap bisa diubah |
| `PATCH /incidents/{id}/sync-status` | Dicek nama role `administrator_sistem` | Dicek permission `delete_data` (hanya admin) | Tidak berubah untuk role bawaan |
| `PUT /incidents/{id}`, `PATCH /animal-geo-zone-visits/{id}/exit` | Cukup `create_data` | Butuh `update_data` | Tidak berubah untuk role bawaan |
| `POST /gps-readings` & `/bulk` | Menerima perangkat kamera/audio | Hanya perangkat `device_type=gps` (`422`) | — |
| Baterai/sinyal dari GPS & `/devices/{id}/captures` | Tidak memicu notifikasi | Memicu `device_alert` seperti `/telemetry` | Notifikasi baru muncul |
| `GET /gps-readings?limit=-1` | Batas 2.000 titik hilang | Batas minimal 1, maks. 5.000 | — |
| Ekspor CSV | Teks `=...` dijalankan sebagai formula di Excel | Diberi awalan `'` | Isi sel diawali `'` untuk teks semacam itu |
| `GET /media-uploads/{id}/file` | — | Header `X-Content-Type-Options: nosniff` | — |
| Upload media insiden | `.flac` ditolak | `.flac` diterima (sama dengan `/ai/upload`) | — |
| CORS | Origin tertulis di kode (`localhost:3000`) | Dari `.env` `FRONTEND_URLS` | Isi domain FE produksi di server |

Test baru: `tests/Feature/SecurityAuditTest.php` (14 test).

### Rilis sesi 2 jam (commit `970a24f`)

| Sebelum | Sesudah |
|---|---|
| Middleware timeout tidak pernah berjalan karena Sanctum menimpa `last_used_at` lebih dulu; token idle tetap diterima | Pengecekan di `Sanctum::authenticateAccessTokensUsing`; aktivitas terakhir dicatat di cache (`CACHE_STORE`) dengan cadangan `last_used_at`; idle > 2 jam = `401` dan token dihapus |

Opsi baru: `API_IDLE_TIMEOUT` (menit, default 120). Test: `tests/Feature/SessionTimeoutTest.php` (7 test).

### Rilis audit skema & fitur AI (commit `4a89333`–`37707b4`)

Kolom nama memakai pola `name_<entitas>`, semua relasi memakai ID, hak akses berbasis permission,
perangkat kamera/audio, analisis gambar & suara, identifikasi individu satwa, insiden & notifikasi otomatis,
laporan agregat, ekspor, dan dokumentasi API.

| Tabel | Sebelum | Sesudah |
|---|---|---|
| `users` | `name`, `role` (enum teks) | `user_id`, `role_id` → roles, `username`, `full_name`, `address`, `phone`, `status` |
| `roles` | `role_name` | `role_id`, `name_role` |
| `permissions` | `permission_name` | `permission_id`, `name_permission` |
| `role_permissions` | kunci gabungan | `role_permission_id` + unik (`role_id`, `permission_id`) |
| `animals` | `name` | `animal_id`, `name_animal` (unik), `distinctive_marks` |
| `incident_reports` | `animal_code` & `location` (teks) | `user_id`, `animal_id`, `species_id`, `zone_id` (FK), `latitude`, `longitude`, `status`, `source` |
| `tracking_devices` | hanya GPS | `device_type` (gps/camera/audio), `zone_id`, `latitude`, `longitude`, `serial_number` |
| `ai_media_uploads` | gambar/video | + audio, `device_id`, `zone_id`, koordinat, `captured_at`, `mime_type`, `file_size` |
| `ai_predictions` | spesies + confidence | + individu satwa, kondisi fisik, perilaku, kategori suara, tingkat ancaman |
| `notifications` | — | + `report_id`, `prediction_id` |
| `aggregate_reports` | — | + `report_data` (JSON statistik) |

---

## 3. Hak akses per role

Akses dicek dari tabel `role_permissions` (middleware `permission:`). Frontend menyembunyikan tombol
berdasarkan `data.user.permissions` dari `/login` atau `/me`; akses yang ditolak menghasilkan `403`.

| Permission | Untuk apa | Konservasionis | Peneliti | Admin | Kebijakan | Petugas loket | Pengunjung |
|---|---|:-:|:-:|:-:|:-:|:-:|:-:|
| `view_data` | Baca semua data, dashboard, AI | ✓ | ✓ | ✓ | ✓ | | |
| `create_data` | Tambah insiden, GPS, kunjungan zona, cuaca, telemetri, rekaman perangkat | ✓ | ✓ | ✓ | | | |
| `update_data` | Ubah insiden, tutup kunjungan zona | ✓ | ✓ | ✓ | | | |
| `upload_media` | Upload ke AI | ✓ | ✓ | ✓ | ✓ | | |
| `verify_ai` | Verifikasi & retry prediksi AI | ✓ | ✓ | ✓ | | | |
| `manage_master_data` | Spesies, satwa, perangkat, zona | | ✓ | ✓ | | | |
| `view_reports` | Laporan agregat | | ✓ | ✓ | ✓ | | |
| `export_data` | Ekspor CSV/GeoJSON | | ✓ | ✓ | ✓ | | |
| `delete_data` | Hapus insiden/prediksi/media/laporan, ubah `sync-status` | | | ✓ | | | |
| `manage_users` | User, token, activity log | | | ✓ | | | |
| `manage_roles` | Role & permission | | | ✓ | | | |
| `book_tickets` | Pesan tiket/tur, bayar, lihat tiket, tulis ulasan | | | ✓ | | | ✓ |
| `manage_tourism` | Kelola rute, pemandu, shift, tarif, pemesanan, verifikasi bayar | | | ✓ | | | |
| `validate_tickets` | Check-in tiket di pintu masuk | ✓ | | ✓ | | ✓ | |
| `sell_tickets` | POS loket: sesi kas, jual tiket walk-in, void, struk | | | ✓ | | ✓ | |

Role `pengunjung` dibuat lewat pendaftaran mandiri `POST /register` dan **tidak punya `view_data`**, jadi
semua data konservasi (satwa, GPS, AI, insiden, pengguna) menolaknya dengan `403`.

Aturan tambahan:

- Insiden hanya bisa diubah oleh pelapornya atau admin.
- Admin tidak bisa menonaktifkan, menghapus, atau mengganti role akunnya sendiri, dan tidak bisa
  mencabut `manage_roles` dari role-nya sendiri.
- Enam role bawaan tidak bisa diganti nama atau dihapus (namanya dipakai untuk target notifikasi).
- `POST /register` hanya membuat akun `pengunjung`; akun staf (termasuk `petugas_loket`) dibuat admin lewat `POST /users`.
- Kasir hanya melihat sesi dan transaksinya sendiri; void hanya oleh kasir penjual selama sesinya terbuka.

---

## 4. Fitur siap pakai

Semua fitur di bawah aktif dan teruji otomatis. `{id}` selalu angka.

### Autentikasi dan profil

| Method | Path | Body / query | Keterangan |
|---|---|---|---|
| POST | `/login` | `{email, password}` | Token + user (role, permissions). Maks. 5 gagal/menit |
| POST | `/logout` | — | Mencabut token aktif |
| POST | `/auth/refresh` | — | Token baru, token lama dicabut |
| GET | `/me` | — | Profil, role, permission |
| PATCH | `/me` | `{full_name?, username?, email?, address?, phone?}` | Role tidak bisa diubah sendiri |
| PUT | `/me/password` | `{old_password, new_password, new_password_confirmation}` | Token lain milik user dicabut |

### Pengguna, role, dan audit (admin)

| Method | Path | Keterangan |
|---|---|---|
| GET, POST | `/users` | Filter `search, role_id, role, status`. Buat: `{username, full_name, email, password, role_id \| role, address?, phone?}` |
| GET, PUT, DELETE | `/users/{id}` | PUT `status: nonaktif` atau `password` baru mencabut semua token user. DELETE = soft delete |
| PATCH | `/users/{id}/status` | `{status: aktif \| nonaktif}` |
| GET | `/users/{id}/activity`, `/users/{id}/tokens` | Riwayat aktivitas, daftar token (tanpa nilai token) |
| DELETE | `/users/{id}/tokens/{tokenId}` | Cabut satu token |
| GET, POST | `/roles` | `{name_role, description?, permission_ids?}` |
| GET, PUT, DELETE | `/roles/{id}` | Role bawaan: nama dikunci, tidak bisa dihapus. Role lain: hapus ditolak `409` jika masih dipakai |
| GET, PUT | `/roles/{id}/permissions` | PUT `{permission_ids: [..]}` mengganti seluruh set |
| POST, DELETE | `/roles/{id}/permissions/{permissionId}` | Tambah / cabut satu permission |
| GET | `/permissions`, `/permissions/{id}` | Hanya baca |
| GET | `/activity-logs`, `/activity-logs/{id}` | Filter `user_id, action, start_date, end_date` |

### Master data

| Method | Path | Izin tulis | Keterangan |
|---|---|---|---|
| GET, POST | `/species` | `manage_master_data` | `{common_name, scientific_name, conservation_status}`; list memuat `animal_count` |
| GET, PUT, DELETE | `/species/{id}` | `manage_master_data` | Hapus ditolak `409` jika masih ada satwa |
| GET, POST | `/animals` | `manage_master_data` | `{species_id, name, gender, birth_date?, distinctive_marks?, is_active?}`; filter `species_id, gender, is_active, device_id, search` |
| GET, PUT, DELETE | `/animals/{id}` | `manage_master_data` | Detail memuat `latest_ai_observation`; hapus = soft delete + lepas kalung |
| PATCH | `/animals/{id}/status` | `manage_master_data` | `{is_active}` |
| GET | `/animals/{id}/track`, `/zone-visits`, `/incidents`, `/ai-observations` | — | Jejak GPS, riwayat zona, insiden, kondisi & perilaku dari AI |
| GET, POST | `/devices` | `manage_master_data` | `{model, device_type?: gps\|camera\|audio, serial_number?, animal_id? (gps), zone_id?, latitude?, longitude?, ...}`; filter `device_type, is_active, device_condition, animal_id, zone_id, battery_low` |
| GET, PUT, DELETE | `/devices/{id}` | `manage_master_data` | Hapus ditolak `409` jika sudah punya data |
| PATCH, DELETE | `/devices/{id}/assign-animal` | `manage_master_data` | `{animal_id, force?}`; `409` jika satwa sudah berkalung |
| PATCH | `/devices/{id}/condition`, `/devices/{id}/status` | `manage_master_data` | `{device_condition}`, `{is_active}` |
| GET | `/devices/{id}/latest-reading`, `/gps-readings`, `/battery-history`, `/captures` | — | `captures` = rekaman kamera/audio + hasil AI |
| GET, POST | `/geo-zones` | `manage_master_data` | `{zone_name, zone_type, center_lat + center_lon + radius_km, atau polygon_geojson}` |
| GET, PUT, DELETE | `/geo-zones/{id}` | `manage_master_data` | Hapus = soft delete |
| GET | `/geo-zones/{id}/animals` (`?history=1`), `/visits`, `/devices`; `/geo-zones/locate?lat=&lon=` | — | Satwa, kunjungan, perangkat di zona; zona dari koordinat |

### Telemetri, insiden, dan notifikasi

| Method | Path | Izin tulis | Keterangan |
|---|---|---|---|
| GET | `/gps-readings` | — | Filter `device_id, animal_id, start_date, end_date, min_lat..max_lon, latest_only=1`. Tanpa pagination: 2.000 terbaru (`limit` 1–5.000) |
| POST | `/gps-readings` | `create_data` | Hanya perangkat GPS. `{device_id, latitude, longitude, recorded_at, altitude_meters?, accuracy_meters?, battery_level?, signal_strength?}`; duplikat waktu = `409` |
| POST | `/gps-readings/bulk` | `create_data` | `{readings: [...]}` maks. 1.000; duplikat dilewati |
| GET | `/gps-readings/{id}`, `/gps-readings/{id}/weather` | — | Data GPS append-only |
| PATCH | `/devices/{id}/telemetry` | `create_data` | Heartbeat `{battery_level?, signal_strength?, recorded_at?}` |
| GET, POST | `/animal-geo-zone-visits` | `create_data` | `{animal_id, zone_id, entered_at, entry_reading_id?}`; `409` jika masih ada kunjungan aktif |
| PATCH | `/animal-geo-zone-visits/{id}/exit` | `update_data` | `{exited_at?}` |
| GET, POST | `/incidents` | `create_data` | `{incident_type, description, timestamp, latitude, longitude, location?, animal_id?, species_id?, zone_id?, status?}`; zona terisi otomatis |
| GET, PUT | `/incidents/{id}` | `update_data` | Ubah hanya oleh pelapor atau admin |
| DELETE | `/incidents/{id}` | `delete_data` | Soft delete |
| PATCH | `/incidents/{id}/sync-status` | `delete_data` | `{sync_status: pending_sync\|synced\|failed}` |
| GET, POST | `/incidents/{id}/media` | `create_data` | Bukti foto/video/audio (`file`, `analyze=1` untuk analisis AI) |
| GET | `/weather-data`, `/weather-data/{id}` | — | Filter `reading_id, source, start_date, end_date` |
| POST | `/weather-data/ingest`, `/weather-data/sync` | `create_data` | Manual / tarik Open-Meteo |
| GET | `/notifications`, `/notifications/unread-count`, `/notifications/{id}` | — | Hanya milik user aktif |
| PATCH | `/notifications/{id}/read`, `/notifications/read-all` | — | |
| DELETE | `/notifications/{id}`, `/notifications` | — | Tanpa id = hapus semua yang sudah dibaca |

Otomatis: setiap titik GPS mencatat masuk/keluar zona dan mengirim `risk_zone` bila satwa masuk permukiman,
perkebunan, atau jalan raya. Baterai < 20%, sinyal lemah, atau perangkat rusak/hilang (dari GPS, rekaman
perangkat, atau telemetri) memicu `device_alert`.

### Dashboard, laporan, dan ekspor

| Method | Path | Izin | Keterangan |
|---|---|---|---|
| GET | `/dashboard`, `/dashboard/stats?period=7`, `/dashboard/charts?period=30` | `view_data` | Statistik, alert perangkat, ancaman & insiden terbaru |
| GET, POST | `/aggregate-reports`, `/aggregate-reports/generate` | `view_reports` | `{report_type: daily\|weekly\|monthly, period_start?, period_end?, overwrite?}` |
| GET | `/aggregate-reports/{id}`, `/aggregate-reports/{id}/download` | `view_reports` | Download CSV |
| DELETE | `/aggregate-reports/{id}` | `delete_data` | |
| GET | `/export/{jenis}` | `export_data` | `gps/csv`, `gps/geojson`, `animals/csv`, `species/csv`, `devices/csv`, `incidents/csv`, `weather/csv`, `ai-predictions/csv`, `geo-zones/csv`, `geo-zones/geojson`, `zone-visits/csv`, `aggregate-report/{id}`; admin juga `users/csv`, `activity-logs/csv` |

Unduhan diambil sebagai blob:

```ts
const res = await fetch(`${API_URL}/export/incidents/csv?start_date=2026-11-01`, {
  headers: { Authorization: `Bearer ${token}` },
});
const url = URL.createObjectURL(await res.blob());
```

---

## 5. Fitur AI

AI mengenali spesies, menebak individu satwa, menilai kondisi fisik dan perilaku, serta mengklasifikasi suara
untuk mendeteksi perburuan dan penebangan liar. Ancaman tingkat sedang ke atas atau satwa terluka/sakit
langsung membuat insiden dan notifikasi; semua hasil tetap menunggu verifikasi petugas.

| `device_type` | Perangkat | Dipasang di | Data yang dikirim |
|---|---|---|---|
| `gps` | Kalung pelacak | Satwa (`animal_id`) | `POST /gps-readings` |
| `camera` | Kamera jebak | Titik tetap (`zone_id`, koordinat) | `POST /devices/{id}/captures` (foto/video) |
| `audio` | Perekam suara | Titik tetap | `POST /devices/{id}/captures` (rekaman, maks. 25 MB) |

| Field prediksi | Isi |
|---|---|
| `predicted_species_id`, `confidence` | Spesies + keyakinan 0–1 |
| `predicted_animal_id`, `animal_match_confidence`, `animal_match_method` | Individu: `appearance_reid`, `gps_proximity`, `zone_presence`, `distinctive_marks`, `single_candidate` |
| `faces` | Daftar wajah: `{status: staff\|possible_staff\|unknown, label, name, similarity, bbox, time_seconds}` (tanpa data biometrik) |
| `staff_face_count`, `unknown_face_count` | Jumlah staf dikenali / wajah tidak dikenal (video: maksimum per frame) |
| `individual_count`, `detected_objects` | Jumlah satwa & kotak deteksi MegaDetector (persen gambar) |
| `physical_condition`, `condition_notes` | `healthy`, `injured`, `sick`, `emaciated`, `unknown` |
| `behavior`, `behavior_notes` | `foraging`, `resting`, `moving`, `running`, `drinking`, `social`, `parenting`, `mating`, `alert`, `aggressive`, `unknown` |
| `sound_category`, `sound_label` | `animal_call`, `gunshot`, `chainsaw`, `tree_falling`, `fire`, `engine`, `vehicle`, `human_voice`, `dog_bark`, `environment`, `unknown` |
| `threat_level` | `none`, `low`, `medium`, `high`, `critical` |
| `processing_status` | `completed`, `unrecognized`, `failed` |

Label bahasa Indonesia tersedia di `GET /ai/vocabulary`. Individu dipilih bila skornya ≥ 0,35 dan unggul ≥ 0,1
dari kandidat kedua. Bukti: kemiripan penampilan dengan galeri satwa (MegaDescriptor; ≥ 0,6 menambah 0,5–1,0,
di bawah 0,6 dengan galeri yang ada membagi dua skor), jarak kalung GPS ±6 jam, zona yang sama, ciri fisik,
kandidat tunggal. Daftar kandidat & bukti ada di `raw_response.identification_candidates`.

Galeri re-identifikasi tiap satwa (perlu `AI_VISION_SERVICE_URL`):

| Method | Path | Izin | Keterangan |
|---|---|---|---|
| GET | `/animals/{id}/reference-photos` | `view_data` | Foto referensi + deteksi terverifikasi (tanpa vektor) |
| POST | `/animals/{id}/reference-photos` | `manage_master_data` | Multipart `file` (jpg/png/webp, maks. 20 MB). Satwa terbesar dipotong otomatis |
| GET | `/animals/{id}/reference-photos/{embeddingId}/file` | `view_data` | Berkas foto referensi |
| DELETE | `/animals/{id}/reference-photos/{embeddingId}` | `manage_master_data` | Keluarkan dari galeri |

```ts
// daftarkan 3-5 foto jelas tiap individu (mis. saat pemasangan kalung)
const form = new FormData();
form.append("file", photo);
await api.post(`/animals/${animalId}/reference-photos`, { formData: form, timeoutMs: 60_000 });
```

| Deteksi | Tingkat | Insiden dibuat |
|---|---|---|
| Tembakan, senjata, jerat | `critical` | `perburuan` |
| Gergaji mesin, pohon tumbang, penebangan | `high` | `perambahan` |
| Api / asap | `high` | `kebakaran_hutan` |
| Mesin berat | `medium` | `perambahan` |
| Kendaraan, anjing, orang di zona lindung | `low`–`medium` | tidak (notifikasi bila `medium`) |
| Satwa terluka atau sakit | `medium` (terluka), `none` (sakit) | `kesehatan_satwa` |

| Method | Path | Izin | Keterangan |
|---|---|---|---|
| POST | `/ai/upload` (= `/media-uploads`) | `upload_media` | Multipart `file` (jpg, png, webp, mp4, mov, webm, wav, mp3, m4a, ogg, flac; maks. 50 MB) + opsional `report_id, device_id, zone_id, latitude, longitude, captured_at`. Maks. 30 upload/menit |
| POST | `/devices/{id}/captures` | `create_data` | Kamera/perekam; opsional `captured_at, battery_level, signal_strength` |
| GET | `/ai-predictions`, `/ai-predictions/pending-verification`, `/ai-predictions/{id}` | `view_data` | Filter status, ancaman, kondisi, perilaku, suara, spesies, individu, perangkat, zona, tanggal |
| POST / PATCH | `/ai-predictions/{id}/verify` | `verify_ai` | `{verification_status: confirmed\|rejected, verified_species_id, verified_animal_id?}` |
| POST | `/ai-predictions/{id}/retry` | `verify_ai` | `409` jika sudah diverifikasi |
| POST | `/media-uploads/{id}/predictions` | `upload_media` | Analisis media yang ada; `409` jika media punya prediksi terverifikasi |
| DELETE | `/ai-predictions/{id}`, `/media-uploads/{id}` | `delete_data` | Bukti terverifikasi tidak bisa dihapus |
| GET | `/media-uploads`, `/media-uploads/{id}`, `/media-uploads/{id}/file`, `/media-uploads/{id}/predictions` | `view_data` | File dari storage privat (butuh token) |
| GET | `/ai/threats?min_level=medium`, `/ai/summary-report`, `/ai/health`, `/ai/vocabulary` | `view_data` | `summary-report` kini memuat `ai_recap` |
| GET | `/ai/recap?start_date=&end_date=` | `view_data` | Rekap data AI untuk tampilan & PDF: `totals`, `species`, `other_labels`, `individuals`, `faces`, `sounds`, `threats.recent`, `by_zone`, `daily`, `models` |
| GET, POST | `/staff-faces` | `manage_users` | Galeri wajah staf. POST multipart `file` + `name` (atau `user_id`); wajah terbesar dipakai, `422` bila tidak ada wajah |
| DELETE | `/staff-faces/{id}`, `/staff-faces` (`{name}`) | `manage_users` | Hapus satu foto / semua foto seorang staf |
| GET | `/staff-faces/{id}/file` | `manage_users` | Foto wajah staf |
| POST | `/ai/detect-objects` | `upload_media` | Analisis cepat tanpa disimpan |

```ts
const form = new FormData();
form.append("file", file);
form.append("report_id", String(reportId)); // opsional
const res = await api.post("/ai/upload", { formData: form, timeoutMs: 120_000 });
// res.data.prediction.physical_condition, .behavior, .predictedAnimal?.name, .threat_level
```

Engine:

| Analisis | Engine utama (terlatih, ai-service) | Cadangan |
|---|---|---|
| Deteksi satwa/manusia/kendaraan, jumlah individu | MegaDetector v5a (`AI_VISION_SERVICE_URL`) | Groq vision |
| Spesies dari gambar | SpeciesNet v4.0.3a, geofence `AI_COUNTRY` | Groq vision |
| Identifikasi individu | MegaDescriptor-T-224 + GPS/zona | GPS/zona/ciri fisik saja |
| Kondisi fisik, perilaku, senjata/jerat/penebangan/api | Groq vision dengan konteks deteksi (belum ada model terlatih) | `unknown` bila Groq tidak dikonfigurasi |
| Suara | YAMNet + classifier & detektor tembakan terlatih (`AI_AUDIO_SERVICE_URL`) | Groq Whisper + LLM, lalu aturan ciri akustik WAV |
| Video | 1 frame tiap 2 detik (maks. 8) di ai-service | 1 frame via `ffmpeg` + Groq |
| Ringkasan laporan | Groq teks (`GROQ_TEXT_MODEL`) | Ringkasan statistik tanpa AI |

Mode antrean (`AI_QUEUE=true`): semua endpoint upload/analisis mengembalikan prediksi `queued`; jalankan
`php artisan queue:work --queue=ai,default`. Status berjalan `queued` → `processing` → `completed`/`unrecognized`/`failed`.

```ts
// polling hasil bila AI_QUEUE=true
let p = res.data.prediction;
while (p.processing_status === "queued" || p.processing_status === "processing") {
  await new Promise((r) => setTimeout(r, 2000));
  p = (await api.get(`/ai-predictions/${p.prediction_id}`)).data;
}
```

---

## 6. Fitur yang masih akan dikembangkan

| Fitur | Kondisi sekarang | Rencana |
|---|---|---|
| CRUD AI lanjutan | Upload, analisis, retry, verifikasi, hapus | Koreksi manual, riwayat versi prediksi, statistik akurasi per spesies |
| Kondisi fisik & perilaku terlatih | Groq vision (tebakan LLM, wajib diverifikasi) | Latih classifier dari prediksi yang diverifikasi petugas (data terkumpul di `ai_predictions`) |
| Spesies Sumatra spesifik | SpeciesNet: orangutan hanya *P. pygmaeus*, harimau tanpa subspesies; badak/gajah/orangutan belum diuji foto Sumatra | Fine-tune dengan foto kamera jebak Sumatra (mis. dari TNGL/WCS) |
| Tembakan jauh di hutan lebat | 63% tertangkap pada rekaman Vietnam | Tambah rekaman tembakan dari lokasi sendiri lalu latih ulang (`ai-service/training`) |
| Pohon tumbang | Aturan zero-shot YAMNet (tidak ada data latih) | Kumpulkan rekaman lalu latih |
| Suara burung per spesies | Kategori umum "suara satwa" | BirdNET (gratis, non-komersial) |
| Notifikasi realtime | FE polling `/notifications` | WebSocket / Laravel Reverb |
| Analisis video penuh | 1 frame tiap 2 detik (maks. 8) + trek suara | Deteksi gerakan, frame lebih rapat untuk video pendek |
| Riwayat baterai | `battery-history` = nilai terakhir | Tabel riwayat telemetri |
| Laporan PDF | CSV | PDF siap cetak |

---

## 7. Hasil audit

Audit terakhir: 2026-11-26. Cakupan: keamanan, validasi, kesesuaian fitur dengan dokumen ini, logika backend,
integrasi frontend, endpoint, dan konsistensi. Setiap temuan yang diperbaiki dibuktikan dulu dengan test.

### Modul wisata: kontrol keamanan

| Risiko | Kontrol | Dites di `TourismTest` |
|---|---|---|
| Pendaftar mengangkat dirinya jadi staf | Role `pengunjung` dipaksa server; `role_id`/`role` dari input diabaikan | ✓ |
| Pengunjung membaca data konservasi | Pengunjung tidak punya `view_data`; semua endpoint internal `403` | ✓ |
| Manipulasi harga/status | Harga dihitung `PricingService`; `total_amount`, `status`, `price_lines` dari input diabaikan | ✓ |
| Overbooking (pemesanan bersamaan) | Baris shift dikunci `lockForUpdate` di dalam transaksi sebelum menghitung sisa kuota | logika |
| Mengintip pemesanan orang lain (IDOR) | Kode pemesanan acak + query selalu difilter `user_id`; selain pemilik `404` | ✓ |
| Tiket palsu / dipakai dua kali | Token HMAC dari `APP_KEY`, dibandingkan `hash_equals`; check-in hanya sekali dan hanya di tanggal kunjungan | ✓ |
| Webhook pembayaran palsu | Wajib tanda tangan HMAC-SHA256 + nominal harus sama + idempoten; tanpa rahasia `503` | ✓ |
| Unggahan berbahaya | Bukti bayar hanya jpg/png/webp/pdf ≤ 5 MB, disimpan privat, dibuka lewat endpoint staf dengan `nosniff` | ✓ |
| XSS lewat ulasan | Tag HTML dibuang sebelum disimpan | ✓ |
| Data pribadi | Kontak pemandu tidak dikirim; nama pengulas disingkat; nomor identitas peserta tidak disimpan | ✓ |
| Brute force / spam | Rate limit per grup (prefix terpisah): daftar 5/menit, login 5 percobaan, pesan & unggah 10/menit, katalog 60/menit, POS jual 60/menit | - |
| POS: kasir mengubah harga | Harga dihitung `PricingService`; input harga/status diabaikan | ✓ `PosTest` |
| POS: kas hilang / void fiktif | Void wajib alasan, hanya kasir penjual & sesi terbuka; tiket void langsung tidak berlaku; selisih kas tercatat permanen saat tutup | ✓ `PosTest` |
| POS: overbooking online + loket | Kuota bersama dikunci `lockForUpdate` | ✓ `PosTest` |
| POS: kasir melihat transaksi kasir lain | Sesi/transaksi difilter `cashier_id`; laporan harian hanya `manage_tourism` | ✓ `PosTest` |

### Sudah diperbaiki

| # | Kategori | Temuan | Perbaikan |
|---|---|---|---|
| 1 | Keamanan | User dinonaktifkan lewat `PUT /users/{id}` tetap bisa memakai token lama | Token dicabut; token user nonaktif ditolak saat autentikasi |
| 2 | Keamanan | Admin bisa menonaktifkan dirinya lewat `PUT /users/{id}` | Ditolak `422` |
| 3 | Keamanan | Reset password oleh admin tidak memutus sesi lama | Token user dicabut |
| 4 | Integritas data | `POST /media-uploads/{id}/predictions` (termasuk pengambil kebijakan) bisa mengembalikan prediksi terverifikasi ke `pending` | `409` bila ada prediksi terverifikasi |
| 5 | Keamanan | Admin bisa mencabut `manage_roles` dari role sendiri (terkunci permanen) atau mengganti nama role bawaan sehingga `sync-status` dan target notifikasi rusak | Role bawaan dikunci, cegah self-lockout |
| 6 | Keamanan | Ekspor CSV rentan formula injection (`=HYPERLINK(...)` dari deskripsi insiden) | Sel teks berbahaya diberi awalan `'` |
| 7 | Keamanan | `?limit=-1` menghapus batas query GPS | Batas 1–5.000 |
| 8 | Konsistensi | `update_data` tidak pernah dicek; `sync-status` dicek nama role | Route memakai `permission:update_data` dan `permission:delete_data` |
| 9 | Logika | Titik GPS diterima untuk kamera/perekam | Hanya `device_type=gps` |
| 10 | Kesesuaian fitur | Baterai/sinyal dari GPS & rekaman tidak memicu `device_alert` (dokumen menyatakan memicu) | Health check dipanggil |
| 11 | Validasi | Media insiden menolak `.flac` | Disamakan dengan `/ai/upload` |
| 12 | Integrasi | Origin CORS tertulis di kode | `FRONTEND_URLS` di `.env` |
| 13 | Keamanan | File media tanpa `nosniff` | Header ditambahkan |
| 14 | Keamanan | Timeout sesi 2 jam tidak berjalan (rilis sebelumnya) | Diperbaiki di `970a24f` |
| 15 | Integrasi | ai-service mati → semua upload gambar/video langsung `failed` | Fallback ke Groq; pesan jelas bila Groq juga tidak ada |
| 16 | Konsistensi | `model_version` hasil fallback Groq tetap tertulis `speciesnet` | `groq:<model>` |
| 17 | Kesesuaian fitur | Galeri wajah, double-count staf per video, `application/mp4`, upload berisi file rusak `500` (rilis wajah) | Diperbaiki & dites di `FaceRecognitionAndRecapTest` |
| 18 | Logika | Jam shift (WIB) dibandingkan dengan jam server UTC: batas pesan & check-in meleset 7 jam | `TourismClock` (`TOURISM_TIMEZONE`) |
| 19 | Keamanan | Semua route throttle berbagi satu penghitung per user/IP | Prefix per grup (`throttle:5,1,register`, dst.) |
| 20 | Keamanan | Rate limit tamu dihitung per IP server FE, bukan pengunjung | FE meneruskan `X-Forwarded-For`; backend hanya memercayainya dari `TRUSTED_PROXIES` (dites `TrustedProxyTest`) |
| 21 | Keamanan | Pendaftaran tanpa CAPTCHA | Soal hitungan bertanda tangan HMAC, sekali pakai, min. 2 detik + honeypot (`AuthTest`) |
| 22 | Integrasi | Token perangkat lapangan kedaluwarsa setelah idle 2 jam | Token perangkat ber-ability `device:{id}`: tanpa timeout idle, hanya kirim data perangkatnya (`DeviceTokenTest`) |
| 23 | Validasi | Status insiden teks bebas; satwa ≠ spesies; zona basi setelah koordinat diubah; zona terhapus bisa dipilih | Enum status, cek silang, hitung ulang zona, tolak zona terhapus (`IncidentValidationTest`) |
| 24 | Keamanan | Perangkat nonaktif/hilang tetap bisa mengirim GPS/telemetri/rekaman | Ditolak (`409`/`422`) |
| 25 | Data | Token idle menumpuk di database | `tokens:prune-idle` terjadwal tiap jam |
| 26 | Integrasi | `/pos/sessions` tanpa `summary` sehingga halaman FE crash | Bentuk data sama dengan detail sesi |
| 27 | Proses | Tidak ada cara cepat memeriksa konfigurasi berbahaya sebelum server dibuka | `php artisan security:check` + `users:reset-default-passwords` |
| 28 | Dokumentasi | `test.md` (Apidog) memakai endpoint & format lama | Diperbarui sesuai `docs/API.md` dan `routes/api.php` |

### Masih berisiko / perlu keputusan

| Area | Temuan | Dampak | Usulan |
|---|---|---|---|
| Sesi | Polling memperpanjang sesi | Sesi tidak habis selama tab terbuka | Putuskan kebijakan polling |
| Konfigurasi | `.env` lokal memakai `APP_DEBUG=true` | Stack trace bocor bila terbawa ke server | `APP_DEBUG=false` di server |
| Konfigurasi | `GROQ_API_KEY` sempat terbagi di luar `.env` | Kunci bisa disalahgunakan | Rotasi kunci |
| Batas file | Audio dari perangkat maks. 25 MB (batas Groq Whisper), upload manual 50 MB | Rekaman panjang ditolak `422` | Potong rekaman di perangkat |
| AI | Test AI memakai respons Groq tiruan; model `qwen/qwen3.8-27b` belum diuji asli | Analisis bisa gagal di produksi | Uji dengan kunci asli |
| AI | Ambang identifikasi (0,35 / 0,1 / ±6 jam) dan aturan suara WAV belum dikalibrasi | Salah identifikasi, alarm palsu | Kalibrasi dengan data lapangan |
| Hak akses | `/users` khusus admin, peneliti kelola master data, kebijakan hanya baca | Menu FE bisa tidak cocok | Konfirmasi dengan tim FE |
| Integrasi FE | `GET /devices` juga mengembalikan kamera/perekam | Halaman kalung bercampur | FE pakai `?device_type=gps` |
| Database | Migrasi lama digabung | `migrate:fresh --seed` menghapus data | Skrip migrasi data bila ada data produksi |
| Data | File media tanpa antivirus & kebijakan retensi (token idle sudah dibersihkan otomatis tiap jam) | Storage membesar | Kebijakan retensi media + pemindai antivirus (ClamAV) di server |
| Proses | Commit tidak ditandatangani | Label "Unverified" | Signing bila wajib |
| AI suara | Tembakan jauh di hutan lebat: 63% (Vietnam) dan 1/6 pada rekaman malam kontinu; kembang api sering terbaca tembakan | Tembakan bisa terlewat | Latih ulang dengan rekaman lokasi sendiri (`ai-service/training`) |
| AI gambar | Spesies Sumatra (badak, gajah, orangutan) belum diuji dengan foto Sumatra; makaka & kancil sering hanya tingkat famili | Spesies bisa kosong/umum | Uji & fine-tune dengan foto kamera jebak Sumatra |
| AI gambar | Kondisi fisik, perilaku, senjata/jerat/penebangan/api masih dari Groq (LLM), bukan model terlatih | Bisa salah/halusinasi | Wajib verifikasi petugas; latih model dari data verifikasi |
| Re-identifikasi | Akurasi diukur pada harimau (ATRW, kemungkinan bagian data latih model); individu baru 13,5% masih bisa salah cocok | Salah individu | Daftarkan 3–5 foto jelas per satwa; naikkan `AI_REID_THRESHOLD` ke 0,7 bila salah cocok sering terjadi |
| Lisensi | MegaDescriptor & ESC-50 non-komersial (CC-BY-NC) | Tidak boleh untuk produk komersial | Ganti model bila proyek dikomersialkan |
| Sumber daya | ai-service dengan semua model butuh ±2,5 GB RAM dan ±3 GB disk; foto ±2,7 detik di CPU | PC lambat saat banyak upload | `AI_QUEUE=true` |
| Server tim (PC + tunnel) | URL `trycloudflare.com` berganti setiap tunnel dinyalakan ulang; server mati saat PC mati/tidur | Tim harus memperbarui `API_URL` | Domain sendiri (named tunnel) atau ngrok domain statis |
| Server tim (PC + tunnel) | Akun seed `password123` bisa dicoba siapa pun yang tahu URL | Akses tidak sah | Jalankan `php artisan users:reset-default-passwords` lalu `php artisan security:check` sebelum URL dibagikan |
| Server tim (PC + tunnel) | `php artisan serve` melayani satu request sekaligus | Request lain menunggu saat ada analisis AI | `AI_QUEUE=true` (FE polling) |
| Antrean | `AI_QUEUE=true` butuh FE polling | Hasil AI tidak tampil bila FE tidak polling | Aktifkan setelah FE siap, atau tetap `false` |
| Galeri wajah staf | `migrate:fresh --seed` mengosongkan `staff_faces`; foto & embedding sengaja tidak di repo | Semua staf terbaca "Tidak dikenal" setelah fresh | Di server pakai `migrate --force`; setelah fresh jalankan `php artisan staff:import-faces <folder> --replace` |
| Fallback Groq | Saat ai-service mati, wajah staf tidak dicocokkan dan orang di gambar dinilai Groq | Staf bisa memicu "aktivitas manusia"; spesies kurang akurat | Pastikan jendela ai-service jalan; `retry` prediksi setelah ai-service hidup (`model_version` diawali `groq:`) |
| Wajah staf | Foto staf dengan wajah menyamping/gelap kadang tidak dikenali (3/19 di uji) dan menjadi "kemungkinan"/tidak dikenal | Staf ditandai pengunjung | Daftarkan 10+ foto per staf dari berbagai sudut & cahaya, termasuk dari kamera lapangan |
| Wajah staf | Kamera jebak sering menangkap wajah kecil/menyamping/malam (IR); uji hanya memakai foto ponsel & LFW | Pengenalan di lapangan lebih rendah | Uji dengan rekaman kamera sungguhan; ambang bisa diubah lewat `AI_FACE_THRESHOLD` |
| Privasi | Embedding & foto wajah adalah data biometrik | Risiko hukum (UU PDP) | Persetujuan tertulis staf; galeri hanya untuk admin (sudah), hapus data staf yang keluar (`DELETE /staff-faces`) |
| AI suara | Tembakan beruntun terbaca suara manusia; sebagian tepuk tangan ikut ditandai "kemungkinan tembakan" | Alarm palsu medium | Latih dengan rekaman tembakan beruntun nyata |
| Wisata: pembayaran | Integrasi Midtrans Snap sudah ada tetapi baru diuji dengan respons tiruan; perlu akun merchant BBTNGL/koperasi | Selama `MIDTRANS_SERVER_KEY` kosong, pembayaran tetap manual | Daftar merchant Midtrans, uji di sandbox dengan kunci asli, atur URL notifikasi |
| Wisata: data | Rute, pemandu, jadwal, dan jasa pemandu di seeder adalah contoh. Libur 2027 sudah diisi dari SKB (hari raya Islam menunggu keputusan Menag; Nyepi 2027 belum diisi karena sumber berbeda 8/9 Maret) | Harga/jadwal tidak sesuai lapangan | Isi data asli lewat `/tourism/admin/*`; cek ulang libur di `/tourism/prices` tiap tahun |
| Wisata: tarif | Tarif PP 36/2024 diambil dari pemberitaan (Kompas/Detik) karena situs BBTNGL tidak bisa diakses saat pengembangan | Selisih bila ada ketentuan lokal | Cocokkan dengan pengumuman resmi BBTNGL; ubah lewat `PUT /tourism/admin/prices/{id}` |
| Wisata: pembatalan | Pengembalian dana (refund) hanya dicatat statusnya; transfer balik dilakukan manual | Dana tertahan bila petugas lupa | SOP refund & integrasi gateway |
| POS: setoran PNBP | Laporan memisahkan PNBP dan jasa pemandu, tetapi setoran ke kas negara (SIMPONI) masih manual | Salah setor bila laporan tidak dicek | SOP rekonsiliasi harian; integrasi SIMPONI bila tersedia |
| POS: printer | Struk dicetak lewat dialog print browser (kertas 58/80 mm); belum ada ESC/POS langsung ke printer thermal | Perlu klik print tiap transaksi | Atur printer thermal sebagai default + kiosk printing Chrome, atau aplikasi bridge ESC/POS |
| POS: offline | Loket butuh koneksi ke server; belum ada mode offline | Loket berhenti saat internet putus | Mode offline + sinkronisasi (butuh desain antikonflik kuota) |
| Wisata: akun | CAPTCHA hitungan + honeypot + rate limit sudah aktif; belum ada verifikasi email | Bot tertarget masih bisa mendaftar | Verifikasi email (butuh SMTP) atau CAPTCHA komersial (Turnstile) bila spam meningkat |

---

## 8. Setup dan update

Setup pertama:

```bash
composer install
cp .env.example .env
php artisan key:generate
# isi DB_*, GROQ_API_KEY, FRONTEND_URLS di .env
php artisan migrate:fresh --seed
php artisan serve
```

Setiap kali menarik perubahan baru di PC pengembangan (data contoh diisi ulang):

```bash
git pull origin main
composer install
php artisan migrate:fresh --seed
php artisan config:clear
```

`migrate:fresh --seed` menghapus seluruh data lalu mengisi ulang data contoh. Di PC yang menjadi server tim,
pakai `php artisan migrate` (data tim tetap ada) dan restart worker; lihat [`DEPLOY.md`](DEPLOY.md).
Perubahan skema berikutnya selalu ditulis sebagai file migrasi baru.

ai-service (sekali saja): lihat [`../ai-service/README.md`](../ai-service/README.md).

Daftarkan wajah staf (ai-service harus berjalan), dari folder dataset dengan satu subfolder per staf
(`face_farrellino/`, `face_nuevalen/`, ...):

```bash
php artisan staff:import-faces C:\path\staf_dataset --replace
``` Jalankan test dengan
`php vendor/bin/phpunit`.
