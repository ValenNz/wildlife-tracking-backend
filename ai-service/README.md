# ai-service (model AI terlatih, gratis, CPU)

Layanan Python yang menjalankan model terlatih untuk backend Laravel. Semua model gratis dan berjalan
tanpa GPU. Hasil uji lengkap ada di [`training/README.md`](training/README.md).

| Endpoint | Fungsi | Model |
|---|---|---|
| `POST /classify-audio` | Tembakan, gergaji mesin, api, mesin, kendaraan, anjing, suara manusia, suara satwa, suara alam | YAMNet (Google) + 2 classifier yang dilatih proyek ini |
| `POST /analyze-image` | Deteksi satwa/manusia/kendaraan, jumlah individu, spesies, embedding individu | SpeciesNet v4.0.3a (MegaDetector v5a + classifier Google) + MegaDescriptor |
| `POST /analyze-video` | Seperti gambar, untuk 1 frame tiap 2 detik (maks. 8 frame) | sama |
| `POST /embed` | Embedding satwa terbesar di foto referensi | MegaDetector + MegaDescriptor |
| `POST /embed-face` | Embedding wajah terbesar di foto staf (pendaftaran) | YuNet + SFace (OpenCV) |
| (di `/analyze-image` & `/analyze-video`) | Wajah manusia + embedding; backend mencocokkan dengan galeri staf | YuNet + SFace |
| (di `/analyze-video`) | Trek suara video diklasifikasi (`audio`) | pipeline suara di atas |
| `GET /health` | Status tiap model | |

Laravel memanggilnya lewat `AI_AUDIO_SERVICE_URL` dan `AI_VISION_SERVICE_URL`. Bila ai-service mati,
backend kembali ke Groq / heuristik, jadi upload tidak pernah gagal karenanya.

## Setup (sekali saja, Windows)

Cara tercepat, dari root repo (bisa diulang kapan saja untuk memperbaiki setup):

```powershell
powershell -ExecutionPolicy Bypass -File scripts\setup-ai-service.ps1
```

Langkah manual yang setara:

Butuh **Python 3.11 atau 3.12 (64-bit)**, sekitar 3 GB ruang disk, dan internet saat pertama kali.
Dari folder `ai-service`:

```powershell
py -3.12 -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install torch torchvision --index-url https://download.pytorch.org/whl/cpu
pip install -r requirements.txt
python download_model.py
```

- Baris `torch ... /whl/cpu` memasang PyTorch versi CPU (lebih kecil). Pasang ini **sebelum** requirements.
- `download_model.py` mengunduh semua model (sekitar 800 MB) ke `models/` dari sumber resmi:
  YAMNet (Google Storage), SpeciesNet (Kaggle Google), MegaDetector (GitHub agentmorris), MegaDescriptor (Hugging Face BVRA).
  Folder `models/` tidak ikut di-commit.
- Hanya butuh suara? `python download_model.py audio` (15 MB) dan jalankan dengan `AI_PRELOAD_VISION=0`.

Linux/macOS: ganti dua baris pertama dengan `python3 -m venv .venv` dan `source .venv/bin/activate`.

## Menjalankan

```powershell
.\.venv\Scripts\Activate.ps1
uvicorn app:app --host 127.0.0.1 --port 8001
```

Memuat semua model butuh ±30 detik dan ±2,5 GB RAM. Cek `http://127.0.0.1:8001/health`: keempat model (audio, vision, reid, faces) harus `"loaded": true`.

`.env` Laravel:

```env
AI_AUDIO_SERVICE_URL=http://127.0.0.1:8001
AI_AUDIO_SERVICE_MODEL=yamnet-audioset-v1+wt-heads-v1
AI_VISION_SERVICE_URL=http://127.0.0.1:8001
AI_COUNTRY=IDN
AI_REID_THRESHOLD=0.6
```

## Kecepatan (CPU)

| Analisis | Waktu |
|---|---|
| Suara 5 detik | ±0,2 detik |
| Foto kamera jebak | ±2,7 detik |
| Video (8 frame) | ±15–20 detik |
| Embedding individu | ±50 ms per satwa |

Untuk banyak upload sekaligus, aktifkan antrean Laravel (`AI_QUEUE=true`).

## Kontrak respons

`POST /classify-audio` (multipart `file`):

```json
{
  "label": "tembakan / ledakan (Gunshot, gunfire)",
  "category": "gunshot",
  "confidence": 0.9196,
  "decision_source": "gunshot_head",
  "gunshot_probability": 0.9196,
  "category_probabilities": {"environment": 0.71, "vehicle": 0.12},
  "summary": "Model terlatih (YAMNet + classifier) mendeteksi tembakan / ledakan sekitar detik ke-1 ...",
  "detected_at_seconds": 0.96,
  "species": null,
  "model": "yamnet-audioset-v1+wt-heads-v1"
}
```

`POST /analyze-image` (multipart `file`, opsional `country` ISO-3, `embed`):

```json
{
  "detections": [
    {"category": "animal", "confidence": 0.98, "bbox": [0.66, 0.36, 0.34, 0.56],
     "species": {"common_name": "sambar", "scientific_name": "Rusa unicolor", "rank": "species", "score": 0.9999},
     "embedding": [0.012, -0.034, "... 768 angka"]}
  ],
  "animal_count": 1, "person_count": 0, "vehicle_count": 0,
  "top_species": {"common_name": "sambar", "scientific_name": "Rusa unicolor", "rank": "species", "score": 0.9999},
  "model": "speciesnet-v4.0.3a", "embedding_model": "MegaDescriptor-T-224"
}
```

`bbox` = `[x, y, lebar, tinggi]` relatif 0–1. `top_species` memilih takson paling spesifik (spesies > genus > famili > ordo).

## Variabel lingkungan

| Variabel | Default | Fungsi |
|---|---|---|
| `AI_SERVICE_TOKEN` | kosong | Bila diisi, request wajib header `X-Api-Key` (isi juga `AI_AUDIO_SERVICE_TOKEN` di Laravel) |
| `AI_COUNTRY` | `IDN` | Filter geografis SpeciesNet (spesies yang mustahil di Indonesia diturunkan ke takson lebih umum) |
| `AI_DETECTION_THRESHOLD` | `0.2` | Ambang deteksi MegaDetector |
| `AI_REID_MIN_DETECTION` | `0.5` | Embedding hanya dibuat untuk deteksi satwa ≥ nilai ini |
| `AI_VIDEO_FRAME_INTERVAL` / `AI_VIDEO_MAX_FRAMES` | `2` / `8` | Pengambilan frame video |
| `AI_PRELOAD_VISION` | `1` | `0` = model visual dimuat saat pertama dipakai |
| `AI_MODEL_DIR` | `./models` | Lokasi semua model |
| `AI_MAX_UPLOAD_MB` | `50` | Batas ukuran berkas |
| `AI_FACE_DETECTION_THRESHOLD` / `AI_FACE_MIN_PX` | `0.7` / `40` | Ambang deteksi wajah YuNet / ukuran wajah minimum (piksel) |

## Lisensi model

YAMNet, SpeciesNet & SFace: Apache-2.0. MegaDetector & YuNet: MIT. **MegaDescriptor: CC-BY-NC-4.0 (non-komersial)**:
gratis untuk konservasi/riset/tugas kampus, tidak untuk produk komersial. Classifier suara proyek ini dilatih dari
dataset CC-BY dan ESC-50 (CC-BY-NC).
