# API Testing Guide for Apidog

Panduan pengujian API Wildlife Tracking TNGL di Apidog (mengacu pada `docs/API.md` dan `routes/api.php`).

## A. Pengaturan Dasar di Apidog

### Base URL
Gunakan salah satu base URL berikut di environment Apidog (`{{base_url}}`):
- `http://127.0.0.1:8000/api`
- `http://127.0.0.1:8000/api/v1` (prefix versi)

### Header Wajib
```json
{
  "Accept": "application/json",
  "Content-Type": "application/json"
}
```
Untuk endpoint unggah berkas (`multipart/form-data`), biarkan Apidog mengisi `Content-Type` otomatis dan tetap kirim `Accept: application/json`.

### Autentikasi Laravel Sanctum (Bearer Token)
1. Kirim `POST /login` dengan email/username dan password yang valid.
2. Ambil `data.token` dari respons dan simpan ke environment variable `{{token}}`.
3. Untuk request terproteksi, kirim header:
   ```json
   {
     "Authorization": "Bearer {{token}}",
     "Accept": "application/json"
   }
   ```
4. **Timeout idle**: token user otomatis dicabut bila idle lebih dari `API_IDLE_TIMEOUT` menit (default 120 menit / 2 jam) dengan respons `401`.
5. **Token perangkat lapangan**: token yang dibuat dari `POST /devices/{id}/api-token` ber-ability `device:{id}`, tidak memiliki timeout idle, dan hanya boleh dipakai pada `PATCH /devices/{id}/telemetry`, `POST /devices/{id}/captures`, `POST /gps-readings`, serta `POST /gps-readings/bulk` milik perangkat tersebut.

### Akun Uji Bawaan (Seeder)
Password default seluruh akun seed adalah `password123` (wajib direset di server via `php artisan users:reset-default-passwords`):

| Role | Email | Hak Akses Ringkas |
|---|---|---|
| `administrator_sistem` | `admin@wildlife.id` | Semua izin (termasuk `manage_users`, `manage_roles`, `manage_tourism`, `sell_tickets`) |
| `peneliti_ekologi` | `peneliti@wildlife.id` | `view_data`, `create_data`, `update_data`, `upload_media`, `verify_ai`, `manage_master_data`, `view_reports`, `export_data` |
| `konservasionis_lapangan` | `lapangan@wildlife.id` | `view_data`, `create_data`, `update_data`, `upload_media`, `verify_ai`, `validate_tickets` |
| `pengambil_kebijakan` | `kebijakan@wildlife.id` | `view_data`, `upload_media`, `view_reports`, `export_data` |
| `petugas_loket` | `loket@wildlife.id` | `sell_tickets`, `validate_tickets` |
| `pengunjung` | `pengunjung@wildlife.id` | `book_tickets` (tidak bisa membaca data konservasi) |

---

## B. Format Respons & Kode Status

### Respons Sukses
```json
{
  "success": true,
  "message": "OK",
  "data": {},
  "errors": null
}
```
- Endpoint daftar mengembalikan array polos pada `data`.
- Pagination opsional: `?page=1&per_page=25` (maks. 200) dengan header `X-Total-Count`, `X-Current-Page`, `X-Per-Page`, `X-Last-Page`.
- Pengurutan opsional: `?orderBy=<kolom>&order=asc|desc` (mengikuti whitelist tiap endpoint).

### Respons Error Umum
| Status | Arti | Contoh Penyebab |
|---|---|---|
| `401` | Unauthenticated | Token tidak valid, sesi idle > 2 jam, atau akun `nonaktif` |
| `403` | Forbidden | Role tidak memiliki permission route terkait |
| `404` | Not Found | Data tidak ditemukan atau bukan milik pengguna (mis. booking pengunjung lain) |
| `409` | Conflict | Relasi masih dipakai (hapus spesies ber-satwa), kuota shift habis, sesi kasir masih terbuka |
| `422` | Validation Error | Input tidak valid; detail per field berada di `errors` |
| `429` | Too Many Requests | Rate limit per grup terlampaui |

---

## C. Autentikasi, Pendaftaran & Profil

| Skenario | Method | Endpoint | Auth | Body / Parameter | Status |
|---|---|---|---|---|---|
| Ambil soal CAPTCHA daftar | GET | `/register/challenge` | Publik | - | 200 |
| Daftar pengunjung mandiri | POST | `/register` | Publik | `{"full_name":"Wisatawan Uji","username":"wisata_uji","email":"wisata@example.com","password":"Password123","password_confirmation":"Password123","captcha_token":"...","captcha_answer":12,"website":""}` | 201/422 |
| Login valid | POST | `/login` | Publik | `{"email":"admin@wildlife.id","password":"password123"}` | 200 |
| Login kredensial salah | POST | `/login` | Publik | `{"email":"admin@wildlife.id","password":"salah"}` | 422 |
| Refresh token | POST | `/auth/refresh` | Bearer | - | 200 |
| Profil & permissions aktif | GET | `/me` | Bearer | - | 200 |
| Ubah profil sendiri | PATCH | `/me` | Bearer | `{"full_name":"Nama Baru","phone":"08123456789"}` | 200/422 |
| Ganti password sendiri | PUT | `/me/password` | Bearer | `{"old_password":"password123","new_password":"PasswordBaru123","new_password_confirmation":"PasswordBaru123"}` | 200/422 |
| Logout (cabut token aktif) | POST | `/logout` | Bearer | - | 200 |

---

## D. Dashboard, Pengguna, Role & Audit Log

### Dashboard (`view_data`)
| Skenario | Method | Endpoint | Auth | Status |
|---|---|---|---|---|
| Ringkasan dashboard | GET | `/dashboard` | Bearer | 200 |
| Statistik dashboard | GET | `/dashboard/stats` | Bearer | 200 |
| Data grafik dashboard | GET | `/dashboard/charts` | Bearer | 200 |

### Users, Token, Wajah Staf & Activity Log (`manage_users`)
| Skenario | Method | Endpoint | Body / Parameter | Status |
|---|---|---|---|---|
| Daftar pengguna | GET | `/users` | `?page=1&per_page=25` | 200 |
| Detail pengguna | GET | `/users/{id}` | - | 200/404 |
| Tambah pengguna | POST | `/users` | `{"full_name":"Petugas Baru","username":"petugas1","email":"petugas1@wildlife.id","password":"Password123","role":"konservasionis_lapangan"}` | 201/422 |
| Ubah pengguna | PUT | `/users/{id}` | `{"full_name":"Petugas Update"}` | 200/404/422 |
| Ubah status pengguna | PATCH | `/users/{id}/status` | `{"status":"nonaktif"}` | 200/422 |
| Hapus pengguna | DELETE | `/users/{id}` | - | 200/404 |
| Aktivitas pengguna | GET | `/users/{id}/activity` | - | 200 |
| Daftar token aktif user | GET | `/users/{id}/tokens` | - | 200 |
| Cabut token tertentu | DELETE | `/users/{id}/tokens/{tokenId}` | - | 200/404 |
| Galeri wajah staf | GET | `/staff-faces` | - | 200 |
| Unggah wajah staf | POST | `/staff-faces` | Multipart: `staff_name`, `file` | 201/422 |
| Unduh berkas wajah staf | GET | `/staff-faces/{id}/file` | - | 200/404 |
| Hapus wajah staf | DELETE | `/staff-faces/{id}` | - | 200/404 |
| Daftar log aktivitas | GET | `/activity-logs` | `?user_id=&action=` | 200 |
| Detail log aktivitas | GET | `/activity-logs/{id}` | - | 200/404 |

### Roles & Permissions (`manage_roles`)
| Skenario | Method | Endpoint | Body | Status |
|---|---|---|---|---|
| Daftar role + permission | GET | `/roles` | - | 200 |
| Tambah role | POST | `/roles` | `{"name_role":"mitra_riset","description":"Mitra riset"}` | 201/422 |
| Detail role | GET | `/roles/{id}` | - | 200/404 |
| Ubah role | PUT | `/roles/{id}` | `{"description":"Deskripsi baru"}` | 200/409/422 |
| Hapus role | DELETE | `/roles/{id}` | - | 200/409 |
| Permission milik role | GET | `/roles/{id}/permissions` | - | 200 |
| Sinkronisasi permission role | PUT | `/roles/{id}/permissions` | `{"permission_ids":[1,2,3]}` | 200/422 |
| Tambah 1 permission ke role | POST | `/roles/{id}/permissions/{permissionId}` | - | 200/409 |
| Cabut 1 permission dari role | DELETE | `/roles/{id}/permissions/{permissionId}` | - | 200/409 |
| Daftar semua permission | GET | `/permissions` | - | 200 |
| Detail permission | GET | `/permissions/{id}` | - | 200/404 |

---

## E. Master Data Konservasi

### Spesies & Model 3D (`view_data` baca; `manage_master_data` kelola)
| Skenario | Method | Endpoint | Body / Parameter | Status |
|---|---|---|---|---|
| Daftar spesies | GET | `/species` | `?search=&conservation_status=` | 200 |
| Detail spesies | GET | `/species/{id}` | - | 200/404 |
| Tambah spesies | POST | `/species` | `{"common_name":"Orangutan Sumatra","scientific_name":"Pongo abelii","conservation_status":"Critically Endangered"}` | 201/422 |
| Ubah profil/hotspot spesies | PUT | `/species/{id}` | `{"local_names":"Mawas","is_public":true,"model_hotspots":[]}` | 200/422 |
| Hapus spesies | DELETE | `/species/{id}` | - | 200/409 |
| Unduh model 3D (.glb) staf | GET | `/species/{id}/model` | - | 200/404 |
| Unggah model 3D (.glb ≤ 30 MB) | POST | `/species/{id}/model` | Multipart: `file`, `model_source`, `model_credit` | 200/422 |
| Hapus model 3D spesies | DELETE | `/species/{id}/model` | - | 200/404 |

### Satwa Individu (`view_data` baca; `manage_master_data` kelola)
| Skenario | Method | Endpoint | Body / Parameter | Status |
|---|---|---|---|---|
| Daftar satwa | GET | `/animals` | `?species_id=&status=` | 200 |
| Detail satwa | GET | `/animals/{id}` | - | 200/404 |
| Tambah satwa | POST | `/animals` | `{"species_id":1,"name_animal":"ORANGUTAN-01","gender":"male","status":"aktif"}` | 201/422 |
| Ubah satwa | PUT | `/animals/{id}` | `{"distinctive_marks":"Bekas luka pelipis kiri"}` | 200/422 |
| Ubah status satwa | PATCH | `/animals/{id}/status` | `{"status":"nonaktif"}` | 200/422 |
| Hapus satwa | DELETE | `/animals/{id}` | - | 200/404 |
| Riwayat jejak GPS satwa | GET | `/animals/{animalId}/track` | `?start_date=&end_date=` | 200 |
| Kunjungan zona satwa | GET | `/animals/{animalId}/zone-visits` | - | 200 |
| Insiden terkait satwa | GET | `/animals/{animalId}/incidents` | - | 200 |
| Observasi AI terkait satwa | GET | `/animals/{animalId}/ai-observations` | - | 200 |
| Galeri foto referensi re-ID | GET | `/animals/{animalId}/reference-photos` | - | 200 |
| Unggah foto referensi re-ID | POST | `/animals/{animalId}/reference-photos` | Multipart: `file` | 201/422 |
| Unduh berkas foto referensi | GET | `/animals/{animalId}/reference-photos/{embeddingId}/file` | - | 200/404 |
| Hapus foto referensi | DELETE | `/animals/{animalId}/reference-photos/{embeddingId}` | - | 200/404 |

### Perangkat Pelacakan (`/devices` atau `/tracking-devices`)
| Skenario | Method | Endpoint | Body / Parameter | Status |
|---|---|---|---|---|
| Daftar perangkat | GET | `/devices` | `?device_type=gps,camera,audio` | 200 |
| Detail perangkat | GET | `/devices/{id}` | - | 200/404 |
| Tambah perangkat | POST | `/devices` | `{"device_type":"gps","serial_number":"GPS-TNGL-01","model":"Lotek GPS-Plus","device_condition":"normal","status":"aktif"}` | 201/422 |
| Ubah perangkat | PUT | `/devices/{id}` | `{"model":"Lotek GPS-Plus v2"}` | 200/422 |
| Hapus perangkat | DELETE | `/devices/{id}` | - | 200/404 |
| Pasang perangkat ke satwa | PATCH | `/devices/{id}/assign-animal` | `{"animal_id":1}` | 200/422 |
| Lepas perangkat dari satwa | DELETE | `/devices/{id}/assign-animal` | - | 200 |
| Ubah kondisi perangkat | PATCH | `/devices/{id}/condition` | `{"device_condition":"normal"}` | 200/422 |
| Ubah status perangkat | PATCH | `/devices/{id}/status` | `{"status":"aktif"}` | 200/422 |
| Update telemetri perangkat | PATCH | `/devices/{id}/telemetry` | `{"battery_level":85,"signal_strength":-72}` | 200/409/422 |
| Pembacaan GPS terbaru | GET | `/devices/{id}/latest-reading` | - | 200/404 |
| Daftar GPS milik perangkat | GET | `/devices/{id}/gps-readings` | - | 200 |
| Riwayat baterai perangkat | GET | `/devices/{id}/battery-history` | - | 200 |
| Daftar tangkapan kamera/audio | GET | `/devices/{id}/captures` | - | 200 |
| Ingest tangkapan perangkat | POST | `/devices/{id}/captures` | Multipart: `file`, `captured_at?` | 201/409/422 |
| Status token perangkat | GET | `/devices/{id}/api-token` | - | 200 |
| Buat token perangkat baru | POST | `/devices/{id}/api-token` | - | 201 |
| Cabut token perangkat | DELETE | `/devices/{id}/api-token` | - | 200 |

### Zona Geografis (`geo-zones`)
| Skenario | Method | Endpoint | Body / Parameter | Status |
|---|---|---|---|---|
| Daftar zona geografis | GET | `/geo-zones` | - | 200 |
| Cari zona dari koordinat | GET | `/geo-zones/locate` | `?lat=3.65&lon=97.75` | 200 |
| Detail zona | GET | `/geo-zones/{id}` | - | 200/404 |
| Tambah zona | POST | `/geo-zones` | `{"zone_name":"Zona Inti A","zone_type":"zona_inti","center_lat":3.65,"center_lon":97.75}` | 201/422 |
| Ubah zona | PUT | `/geo-zones/{id}` | `{"zone_name":"Zona Inti A1"}` | 200/422 |
| Hapus zona | DELETE | `/geo-zones/{id}` | - | 200/404 |
| Satwa dalam zona | GET | `/geo-zones/{id}/animals` | `?history=1` | 200 |
| Kunjungan zona | GET | `/geo-zones/{id}/visits` | - | 200 |
| Perangkat dalam zona | GET | `/geo-zones/{id}/devices` | - | 200 |

---

## F. Telemetri, Lapangan, Cuaca & Notifikasi

| Skenario | Method | Endpoint | Body / Parameter | Status |
|---|---|---|---|---|
| Daftar GPS reading | GET | `/gps-readings` | `?device_id=&animal_id=&start_date=&end_date=&latest_only=&limit=2000` | 200 |
| Ingest 1 titik GPS | POST | `/gps-readings` | `{"device_id":1,"latitude":3.65862,"longitude":97.731964,"recorded_at":"2026-11-25 10:00:00","battery_level":88,"signal_strength":-70}` | 201/409/422 |
| Ingest bulk GPS | POST | `/gps-readings/bulk` | `{"readings":[{"device_id":1,"latitude":3.659,"longitude":97.732,"recorded_at":"2026-11-25 10:05:00"}]}` | 201/422 |
| Detail GPS reading | GET | `/gps-readings/{id}` | - | 200/404 |
| Cuaca pada GPS reading | GET | `/gps-readings/{readingId}/weather` | - | 200/404 |
| Daftar kunjungan zona | GET | `/animal-geo-zone-visits` | - | 200 |
| Catat masuk zona | POST | `/animal-geo-zone-visits` | `{"animal_id":1,"zone_id":1,"entered_at":"2026-11-25 10:00:00"}` | 201/422 |
| Catat keluar zona | PATCH | `/animal-geo-zone-visits/{id}/exit` | `{"exited_at":"2026-11-25 12:00:00"}` | 200/422 |
| Daftar insiden (`/incidents`) | GET | `/incidents` | `?status=&incident_type=` | 200 |
| Buat laporan insiden | POST | `/incidents` | `{"incident_type":"observasi","description":"Jejak segar di jalur patroli","latitude":3.65,"longitude":97.73,"status":"Menunggu"}` | 201/422 |
| Detail insiden | GET | `/incidents/{id}` | - | 200/404 |
| Ubah insiden (`update_data`) | PUT | `/incidents/{id}` | `{"status":"Diselidiki"}` (baku: `Menunggu`, `Diselidiki`, `Selesai`, `Ditolak`) | 200/422 |
| Ubah sync-status (`delete_data`) | PATCH | `/incidents/{id}/sync-status` | `{"sync_status":"synced"}` | 200/422 |
| Hapus insiden (`delete_data`) | DELETE | `/incidents/{id}` | - | 200/404 |
| Daftar media insiden | GET | `/incidents/{id}/media` | - | 200 |
| Unggah media insiden | POST | `/incidents/{id}/media` | Multipart: `file`, opsional `analyze=1` | 201/422 |
| Daftar data cuaca | GET | `/weather-data` | - | 200 |
| Detail data cuaca | GET | `/weather-data/{id}` | - | 200/404 |
| Ingest cuaca manual | POST | `/weather-data/ingest` | `{"latitude":3.65,"longitude":97.75,"temperature":26.5,"humidity":82,"recorded_at":"2026-11-25 10:00:00"}` | 201/422 |
| Sinkron cuaca Open-Meteo | POST | `/weather-data/sync` | `{"latitude":3.65,"longitude":97.75}` | 200/422 |
| Daftar notifikasi milik user | GET | `/notifications` | - | 200 |
| Jumlah notifikasi belum dibaca | GET | `/notifications/unread-count` | - | 200 |
| Tandai semua dibaca | PATCH | `/notifications/read-all` | - | 200 |
| Tandai 1 notifikasi dibaca | PATCH | `/notifications/{id}/read` | - | 200/404 |
| Hapus semua yang sudah dibaca | DELETE | `/notifications` | - | 200 |
| Hapus 1 notifikasi | DELETE | `/notifications/{id}` | - | 200/404 |

---

## G. Analisis AI, Media, Laporan Agregat & Ekspor

### AI & Media (`view_data`, `upload_media`, `verify_ai`)
| Skenario | Method | Endpoint | Body / Parameter | Status |
|---|---|---|---|---|
| Unggah & analisis media AI | POST | `/ai/upload` | Multipart: `file` (gambar/video/audio), `device_id?`, `zone_id?`, `report_id?` | 201/422 |
| Deteksi objek cepat tanpa simpan | POST | `/ai/detect-objects` | Multipart: `file` | 200/422 |
| Daftar prediksi AI | GET | `/ai-predictions` | `?processing_status=&verification_status=&threat_level=&min_threat=` | 200 |
| Antrean verifikasi (ancaman tertinggi) | GET | `/ai-predictions/pending-verification` | - | 200 |
| Detail prediksi AI | GET | `/ai-predictions/{id}` | - | 200/404 |
| Verifikasi prediksi (`verify_ai`) | PATCH | `/ai-predictions/{id}/verify` | `{"verification_status":"verified","verified_species_id":1,"verified_animal_id":1}` | 200/422 |
| Analisis ulang prediksi (`verify_ai`) | POST | `/ai-predictions/{id}/retry` | - | 200/409 |
| Daftar media upload | GET | `/media-uploads` | - | 200 |
| Detail media upload | GET | `/media-uploads/{id}` | - | 200/404 |
| Unduh file media privat | GET | `/media-uploads/{id}/file` | - | 200/404 |
| Picu prediksi ulang dari media | POST | `/media-uploads/{mediaId}/predictions` | - | 201/409 |
| Daftar ancaman AI | GET | `/ai/threats` | `?min_level=medium` | 200 |
| Ringkasan eksekutif AI | GET | `/ai/summary-report` | - | 200 |
| Rekap data AI | GET | `/ai/recap` | `?start_date=&end_date=` | 200 |
| Kosakata label AI (Indonesia) | GET | `/ai/vocabulary` | - | 200 |
| Status kesehatan engine AI | GET | `/ai/health` | - | 200 |

### Laporan Agregat (`view_reports`) & Ekspor (`export_data`)
| Skenario | Method | Endpoint | Body / Parameter | Status |
|---|---|---|---|---|
| Daftar laporan agregat | GET | `/aggregate-reports` | - | 200 |
| Generate laporan agregat | POST | `/aggregate-reports/generate` | `{"report_type":"weekly","start_date":"2026-11-01","end_date":"2026-11-25"}` | 201/422 |
| Detail laporan agregat | GET | `/aggregate-reports/{id}` | - | 200/404 |
| Unduh CSV laporan agregat | GET | `/aggregate-reports/{id}/download` | - | 200/404 |
| Hapus laporan (`delete_data`) | DELETE | `/aggregate-reports/{id}` | - | 200/404 |
| Ekspor GPS CSV | GET | `/export/gps/csv` | `?start_date=&end_date=` | 200 |
| Ekspor GPS GeoJSON | GET | `/export/gps/geojson` | `?start_date=&end_date=` | 200 |
| Ekspor GeoJSON zona | GET | `/export/geo-zones/geojson` | - | 200 |
| Ekspor entitas CSV lainnya | GET | `/export/{animals\|species\|devices\|incidents\|weather\|ai-predictions\|geo-zones\|zone-visits}/csv` | `?start_date=&end_date=` | 200 |
| Ekspor user & log (`manage_users`) | GET | `/export/{users\|activity-logs}/csv` | `?start_date=&end_date=` | 200 |

---

## H. Ensiklopedia Satwa 3D, Wisata, POS Loket & Pembayaran

### Ensiklopedia Satwa 3D (Publik, tanpa login)
| Skenario | Method | Endpoint | Parameter | Status |
|---|---|---|---|---|
| Katalog satwa 3D publik | GET | `/wildlife/species` | `?search=&taxon_class=&conservation_status=` | 200 |
| Profil lengkap satwa 3D | GET | `/wildlife/species/{id}` | - | 200/404 |
| Unduh file GLB model 3D | GET | `/wildlife/species/{id}/model` | - | 200/404 |

### Wisata Publik (`/tourism/*`)
| Skenario | Method | Endpoint | Body / Parameter | Status |
|---|---|---|---|---|
| Daftar rute wisata | GET | `/tourism/routes` | - | 200 |
| Detail rute + 30 shift terdekat | GET | `/tourism/routes/{slug}` | - | 200/404 |
| Daftar pemandu | GET | `/tourism/guides` | - | 200 |
| Detail pemandu + jadwal | GET | `/tourism/guides/{id}` | - | 200/404 |
| Foto pemandu | GET | `/tourism/guides/{id}/photo` | - | 200/404 |
| Jadwal shift & sisa kuota | GET | `/tourism/schedule` | `?from=2026-11-25&to=2026-12-01&route_id=` | 200 |
| Daftar tarif PNBP & hari libur | GET | `/tourism/prices` | - | 200 |
| Hitung simulasi tarif (quote) | POST | `/tourism/quote` | `{"route_id":1,"visit_date":"2026-11-26","wni_count":2,"wna_count":0,"student_count":0,"drone_count":0}` | 200/422 |
| Info rekening/QRIS tujuan | GET | `/tourism/payment-info` | - | 200 |

### Pemesanan Pengunjung (`book_tickets`)
| Skenario | Method | Endpoint | Body / Parameter | Status |
|---|---|---|---|---|
| Daftar pemesanan saya | GET | `/visitor/bookings` | - | 200 |
| Buat pemesanan tiket | POST | `/visitor/bookings` | `{"route_id":1,"shift_id":1,"wni_count":2,"wna_count":0,"student_count":0,"drone_count":0,"contact_name":"Budi","contact_phone":"08123456789","emergency_contact":"08129876543","payment_method":"bank_transfer","participants":[{"full_name":"Budi","visitor_type":"wni"},{"full_name":"Sari","visitor_type":"wni"}]}` | 201/409/422 |
| Detail pemesanan | GET | `/visitor/bookings/{code}` | - | 200/404 |
| Batalkan pemesanan | POST | `/visitor/bookings/{code}/cancel` | - | 200/409 |
| Unggah bukti bayar | POST | `/visitor/bookings/{code}/payment-proof` | Multipart: `file` (jpg/png/webp/pdf ≤ 5 MB) | 200/422 |
| Bayar online (Midtrans Snap) | POST | `/visitor/bookings/{code}/pay-online` | - | 200/503 |
| Simulasi pembayaran otomatis (gratis) | POST | `/visitor/bookings/{code}/simulate-payment` | `{"channel":"qris","outcome":"paid"}` | 200/409/503 |
| Ambil payload tiket QR | GET | `/visitor/bookings/{code}/ticket` | - | 200/409 |
| Kirim ulasan pasca check-in | POST | `/visitor/bookings/{code}/review` | `{"route_rating":5,"guide_rating":5,"comment":"Pemandu informatif"}` | 201/409/422 |

### Check-In Tiket (`validate_tickets`) & POS Loket (`sell_tickets`, `manage_tourism`)
| Skenario | Method | Endpoint | Body / Parameter | Status |
|---|---|---|---|---|
| Scan/validasi tiket QR | POST | `/tourism/check-in` | `{"ticket":"KODE.TOKEN_HMAC"}` | 200/409/422 |
| Daftar check-in hari ini (WIB) | GET | `/tourism/check-in/today` | - | 200 |
| Sesi kasir terbuka saat ini | GET | `/pos/session` | - | 200 |
| Buka sesi kasir | POST | `/pos/session/open` | `{"counter_name":"Loket Utama","opening_cash":200000}` | 201/409 |
| Tutup sesi kasir | POST | `/pos/session/close` | `{"counted_cash":250000,"note":"Sesuai fisik"}` | 200/409 |
| Riwayat sesi kasir | GET | `/pos/sessions` | `?date=2026-11-25` | 200 |
| Detail sesi kasir | GET | `/pos/sessions/{id}` | - | 200/404 |
| Daftar transaksi sesi aktif | GET | `/pos/sales` | - | 200 |
| Penjualan walk-in loket | POST | `/pos/sales` | `{"route_id":1,"shift_id":1,"wni_count":2,"wna_count":0,"student_count":0,"contact_name":"Walk-in","payment_method":"cash","received_amount":50000,"check_in_now":true}` | 201/409/422 |
| Detail transaksi / cetak ulang | GET | `/pos/sales/{code}` | - | 200/404 |
| Void transaksi (sesi terbuka) | POST | `/pos/sales/{code}/void` | `{"reason":"Salah input jumlah pengunjung"}` | 200/409/422 |
| Laporan harian POS + online (`manage_tourism`) | GET | `/pos/report` | `?date=2026-11-25` | 200 |

### Pengelolaan Wisata (`manage_tourism`, prefix `/tourism/admin`) & Webhook
| Skenario | Method | Endpoint | Body / Parameter | Status |
|---|---|---|---|---|
| Kelola rute wisata | GET/POST/PUT/DELETE | `/tourism/admin/routes`, `/tourism/admin/routes/{id}` | Data rute | 200/201 |
| Kelola pemandu & foto | GET/POST/PUT/DELETE | `/tourism/admin/guides`, `/tourism/admin/guides/{id}/photo` | Data pemandu / multipart `file` | 200/201 |
| Kelola shift | GET/POST/PUT/DELETE | `/tourism/admin/shifts`, `/tourism/admin/shifts/{id}` | Data shift | 200/201 |
| Kelola tarif & hari libur | GET/PUT/POST/DELETE | `/tourism/admin/prices`, `/tourism/admin/holidays` | Data tarif / tanggal libur | 200/201 |
| Kelola pemesanan & refund | GET/POST | `/tourism/admin/bookings`, `/tourism/admin/bookings/{code}/cancel` | - | 200 |
| Verifikasi bukti pembayaran | GET/POST | `/tourism/admin/payments`, `/tourism/admin/payments/{id}/proof`, `/tourism/admin/payments/{id}/verify` | `{"approve":true}` | 200/422 |
| Moderasi ulasan pengunjung | GET/PATCH | `/tourism/admin/reviews`, `/tourism/admin/reviews/{id}` | `{"is_visible":true}` | 200 |
| Webhook payment gateway generik | POST | `/payments/webhook` | Header `X-Signature` HMAC-SHA256; body `{"reference":"...","status":"paid","amount":30000,"transaction_id":"TRX-1"}` | 200/401/503 |
| Notifikasi Midtrans Snap | POST | `/payments/midtrans/notification` | Payload standar Midtrans + `signature_key` SHA-512 | 200/401 |

---

## I. Tips Pengujian di Apidog

- Simpan variabel `base_url`, `token`, `device_token`, dan `booking_code` di Environment Apidog agar mudah berpindah antara lokal (`127.0.0.1:8000`) dan URL tunnel tim.
- Gunakan **Post-processor Script** di request `POST /login` untuk otomatis mengisi `{{token}}` dari `response.data.token`.
- Untuk menguji batas keamanan: uji `pengunjung@wildlife.id` memanggil `GET /animals` (harus `403`), uji daftar tanpa CAPTCHA (harus `422`), dan uji token perangkat memanggil endpoint di luar perangkatnya (harus `403`).
- Dokumentasi skema interaktif lokal tersedia otomatis di `GET http://127.0.0.1:8000/docs/api` (Scramble).
