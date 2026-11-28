# Menjadikan PC sebagai server tim (Cloudflare Tunnel, gratis)

PC Anda menjalankan backend, database, ai-service, dan worker antrean. Cloudflare Tunnel memberi
URL publik HTTPS tanpa membuka port router. Tim frontend cukup mengganti `API_URL` ke URL itu,
tanpa memasang PHP, MySQL, atau Python.

Batasan: server hanya bisa diakses selama PC menyala, tidak tidur, dan terhubung internet.

## 1. Persiapan sekali saja

1. Backend sudah jalan normal di PC (`composer install`, `.env`, database MySQL).
2. ai-service sudah di-setup (lihat [`ai-service/README.md`](../ai-service/README.md)).
3. Pasang Cloudflare Tunnel (gratis, tanpa akun untuk mode cepat):

   ```powershell
   winget install --id Cloudflare.cloudflared
   ```

4. Atur `.env` untuk server bersama:

   ```env
   APP_ENV=production
   APP_DEBUG=false
   QUEUE_CONNECTION=database
   AI_QUEUE=true
   AI_AUDIO_SERVICE_URL=http://127.0.0.1:8001
   AI_AUDIO_SERVICE_MODEL=yamnet-audioset-v1+wt-heads-v1
   AI_VISION_SERVICE_URL=http://127.0.0.1:8001
   AI_COUNTRY=IDN
   FRONTEND_URLS=http://localhost:3000,http://127.0.0.1:3000
   ```

   - `APP_DEBUG=false`: stack trace tidak bocor ke publik.
   - `AI_QUEUE=true`: `php artisan serve` hanya melayani satu request sekaligus (di Windows tidak bisa
     multi-worker), dan Cloudflare memutus request di atas 100 detik. Dengan antrean, upload langsung
     dibalas `queued` dan analisis berjalan di worker, jadi anggota tim lain tidak ikut menunggu.
     Frontend perlu polling `GET /ai-predictions/{id}` sampai `processing_status` bukan `queued`/`processing`.
   - `FRONTEND_URLS`: origin tempat frontend dibuka. Bila tim menjalankan frontend di laptop masing-masing
     (`localhost:3000`), nilai default sudah cukup.

5. Jalankan migrasi (tabel antrean ada di migrasi baru):

   ```powershell
   php artisan migrate
   php artisan config:clear
   ```

6. **Ganti password akun seed.** Akun `admin@`, `peneliti@`, `lapangan@`, `kebijakan@wildlife.id` dengan
   `password123` bisa dicoba siapa pun yang tahu URL-nya. Ubah lewat `PUT /me/password` atau `PUT /users/{id}`,
   lalu bagikan password baru hanya ke tim.

7. Matikan mode tidur PC selama dipakai sebagai server (Settings → System → Power → Sleep: Never).

## 2. Menyalakan server

Dari root repo:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\start-server.ps1
```

Skrip membuka empat jendela: Laravel (`:8000`), worker antrean, ai-service (`:8001`), dan Cloudflare Tunnel.
Di jendela tunnel muncul URL seperti:

```
https://kata-kata-acak.trycloudflare.com
```

Bagikan ke tim. Base URL API = `https://kata-kata-acak.trycloudflare.com/api`.

Tanpa skrip, jalankan masing-masing di jendela terpisah:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
php artisan queue:work --queue=ai,default --tries=2 --timeout=300
cd ai-service; .\.venv\Scripts\Activate.ps1; uvicorn app:app --host 127.0.0.1 --port 8001
cloudflared tunnel --url http://127.0.0.1:8000
```

Cek dari HP atau laptop lain: `GET https://.../up` harus 200, dan `GET https://.../api/ai/health`
(dengan token) menampilkan `audio_service.reachable: true`.

## 3. Untuk tim frontend

```env
# .env.local frontend
NEXT_PUBLIC_API_URL=https://kata-kata-acak.trycloudflare.com/api
```

Nama variabelnya mengikuti yang dipakai frontend. Tidak ada perubahan kode selain URL.

## 4. URL berubah setiap tunnel dinyalakan ulang

Mode cepat (`trycloudflare.com`) gratis tanpa akun, tetapi **URL-nya acak dan berganti setiap kali
`cloudflared` dijalankan ulang**. Setiap restart, bagikan URL baru ke tim.

Pilihan bila ingin URL tetap:

| Pilihan | Biaya | Catatan |
|---|---|---|
| Cloudflare named tunnel | Gratis untuk tunnel, tetapi butuh domain sendiri di Cloudflare (domain berbayar, murah per tahun) | URL tetap, mis. `api.domainanda.id` |
| ngrok, domain statis gratis | Gratis (akun ngrok) | Satu domain tetap; untuk request dari browser ngrok dapat menampilkan halaman peringatan yang perlu dilewati dengan header `ngrok-skip-browser-warning` |

## 5. Update backend saat server dipakai tim

`migrate:fresh --seed` menghapus semua data yang sedang dipakai tim. Di PC yang berfungsi sebagai server:

```powershell
git pull origin main
composer install
php artisan migrate
php artisan config:clear
```

Bila `ai-service/` berubah, jalankan juga `powershell -ExecutionPolicy Bypass -File scripts\setup-ai-service.ps1`.
Lalu tutup dan jalankan ulang `scripts\start-server.ps1` (worker antrean harus di-restart agar memakai kode baru).
`migrate:fresh --seed` hanya dijalankan bila diperlukan dan diumumkan dulu ke tim.

## 6. Masalah umum

| Gejala | Penyebab | Solusi |
|---|---|---|
| `'cloudflared' tidak ditemukan` | winget memasang di `C:\Program Files (x86)\cloudflared\` tanpa menambah PATH | `start-server.ps1` kini mencari di sana otomatis; manual: `& "C:\Program Files (x86)\cloudflared\cloudflared.exe" tunnel --url http://127.0.0.1:8000` |
| Frontend kena CORS error | Origin frontend tidak ada di `FRONTEND_URLS` | Tambahkan, lalu `php artisan config:clear` |
| Prediksi tetap `queued` | Worker antrean tidak jalan | Jalankan `php artisan queue:work --queue=ai,default` |
| `audio_service.reachable: false` | ai-service mati atau model belum diunduh | Jalankan uvicorn; cek `http://127.0.0.1:8001/health` |
| Upload: "analisis AI gagal", `ai_summary` berisi "ai-service tidak bisa dihubungi" / "Connection refused" | uvicorn (jendela ai-service) tidak jalan atau masih memuat model | Jalankan ai-service, tunggu `/health` OK, lalu `POST /ai-predictions/{id}/retry` |
| `502`/`530` dari Cloudflare | Laravel mati atau port berbeda | Pastikan `php artisan serve` di port yang sama dengan tunnel |
| Semua request lambat saat ada upload | `AI_QUEUE=false` dengan `php artisan serve` | Set `AI_QUEUE=true` |
