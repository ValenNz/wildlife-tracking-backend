# Pelatihan & evaluasi model AI

Semua angka di bawah diukur pada **data uji yang tidak dipakai saat pelatihan** (pembagian per rekaman/fold,
bukan acak per klip, agar tidak bocor). Diuji 2026-11-22 di CPU.

## Ringkasan

| Fitur | Model | Asal | Hasil uji |
|---|---|---|---|
| Kategori suara | YAMNet + classifier terlatih (`heads/category_head.npz`) | Dilatih proyek ini | Akurasi 96,1% (2.548 klip) |
| Tembakan | YAMNet pita 0–4 kHz + detektor terlatih (`heads/gunshot_head.npz`) | Dilatih proyek ini | 85% tertangkap, 1,3% alarm palsu dari suara alam/satwa |
| Deteksi satwa/manusia/kendaraan | MegaDetector v5a | Microsoft / agentmorris (MIT) | 79/90 (88%) foto bersatwa terdeteksi, 0/20 foto kosong salah deteksi |
| Spesies | SpeciesNet v4.0.3a | Google (Apache-2.0) | Lihat tabel per spesies |
| Identifikasi individu | MegaDescriptor-T-224 | WildlifeDatasets (CC-BY-NC-4.0) | Top-1 97,9% (107 harimau) |

## Data

| Dataset | Isi | Lisensi | Sumber |
|---|---|---|---|
| Gunshot PAM Vietnam + Belize | Tembakan & suara hutan dari pemantauan akustik pasif (Chu Mom Ray NP), 2 rekaman malam kontinu | CC-BY-4.0 | zenodo.org/records/13893977 |
| Lund anti-poaching | Tembakan senapan serbu/berburu/shotgun pada 10, 50, 150, 350 m; suara singa & hyena (Limpopo) | CC-BY-4.0 | zenodo.org/records/14824927 |
| ESC-50 | 35 kategori × 40 klip: gergaji, api, hujan, petir, angin, serangga, burung, anjing, mesin, manusia, dll. | CC-BY-NC | github.com/karolpiczak/ESC-50 |
| SWG Camera Traps | Foto kamera jebak berlabel dari Laos/Vietnam (sambar, babi hutan, kijang, makaka, kancil, musang) | CDLA-permissive | lila.science/datasets/swg-camera-traps |
| ATRW | 1.887 foto 107 harimau Amur dengan ID individu | CC-BY-NC-SA | lila.science/datasets/atrw |

Data tidak di-commit; unduh dari sumber di atas.

## Suara

### Kenapa perlu dilatih

YAMNet tanpa pelatihan tambahan (aturan zero-shot) hanya menangkap **2 dari 19 (11%)** tembakan di rekaman hutan
Vietnam (tembakan jauh & pelan), dan membaca **72% klip petir** sebagai tembakan.

### Langkah

```bash
# fitur YAMNet (embedding mean+max + skor kelas maks); BAND_LIMIT=4000 untuk detektor tembakan
python training/extract_features.py . vn_all.npz "vn_bz_train_gun=.../train/gunshot/*.wav" ...
BAND_LIMIT=4000 python training/extract_features.py . vn_nb.npz ...
python training/train_gunshot.py vn_nb.npz esc_nb.npz lund_nb.npz heads/gunshot_head.npz 0.003
python training/train_category.py . <folder npz> heads/category_head.npz 0.003 0.5 0.7
```

Pembagian: ESC-50 fold 1–3 latih, 4 validasi, 5 uji; Lund per indeks (60/20/20); rekaman malam kontinu Vietnam
dibagi per blok waktu berurutan. Ambang & veto dipilih **hanya** dari data validasi.

### Masalah yang ditemukan & diperbaiki saat pelatihan

1. **Shortcut sample rate.** Tembakan Belize direkam 8 kHz, noise 16 kHz: model belajar "tidak ada frekuensi tinggi =
   tembakan". Solusi: semua audio detektor tembakan dibatasi ke pita 0–4 kHz saat latih maupun pakai.
2. **Negatif kurang beragam.** Detektor yang hanya melihat noise hutan Vietnam menganggap hampir semua suara keras
   (gergaji, hujan, babi) tembakan. Solusi: tambah ESC-50, singa, hyena sebagai negatif.
3. **Petir & suara manusia impulsif.** Veto: aturan zero-shot tembakan diabaikan bila classifier yakin ≥ 0,7 suara alam;
   detektor tembakan diabaikan bila classifier yakin ≥ 0,5 suara manusia.

### Hasil uji akhir (n = 2.548)

| Kategori | Benar | Salah terbanyak |
|---|---|---|
| Tembakan | 74/87 (85%) | 12 terbaca suara alam |
| Gergaji mesin | 14/16 (88%) | kendaraan 1, manusia 1 |
| Api | 6/8 (75%) | kendaraan 1, alam 1 |
| Mesin | 8/8 (100%) | – |
| Kendaraan | 17/24 (71%) | tembakan 3, alam 2, mesin 2 |
| Anjing | 8/8 (100%) | – |
| Suara manusia | 60/64 (94%) | tembakan 2 |
| Suara satwa | 268/282 (95%) | alam 9 |
| Suara alam | 1.994/2.051 (97%) | satwa 31, tembakan 19 |

Alarm palsu (suara alam/satwa → ancaman): **31/2.333 = 1,3%**. Pengecoh: kembang api sering terbaca tembakan
(perilaku yang bisa diterima di hutan lindung).

Per sumber tembakan (detektor tembakan saja): Lund 60/62 (97%, termasuk 350 m); Vietnam 12/19 (63%);
rekaman malam kontinu 1/6 dengan 0,8% alarm palsu. **Tembakan jauh di hutan lebat tetap yang paling sulit.**

## Gambar: SpeciesNet

110 foto kamera jebak SWG (Laos/Vietnam), satu foto per sekuens, maksimal 3 per lokasi, geofence negara asal:

| Label | Benar (tingkat spesies) | Catatan |
|---|---|---|
| Sambar | 14/15 | 1 tidak terdeteksi |
| Babi hutan | 13/15 | |
| Kosong | 20/20 | tidak ada deteksi palsu |
| Kijang besar | 10/12 | |
| Kijang merah | 7/8 | 4 di tingkat genus |
| Makaka ekor tunggul | 7/8 | |
| Musang | 6/6 | |
| Makaka beruk | 0/12 | 7 benar di tingkat famili *Cercopithecidae* |
| Kancil | 0/8 | 4 benar di tingkat famili *Tragulidae* |
| Burung | 1/6 | |

Waktu proses ±2,7 detik/foto di CPU. Label SpeciesNet mencakup harimau (*Panthera tigris*), badak Sumatra,
gajah Asia, sambar, dan orangutan (hanya *Pongo pygmaeus*; dipetakan ke *Pongo abelii* lewat genus).
**Badak, gajah, dan orangutan belum diuji dengan foto Sumatra nyata** (tidak ada dataset publik).

## Identifikasi individu: MegaDescriptor

ATRW (107 harimau, 1.887 foto), 53 ms/foto:

| Skenario | Hasil |
|---|---|
| Leave-one-out | top-1 97,9%, top-5 99,6% |
| Galeri 3 foto/individu, ambang 0,5 | terdaftar benar 93,9%, individu baru ditolak 72,0% |
| Galeri 3 foto/individu, **ambang 0,6 (dipakai)** | terdaftar benar 92,7%, individu baru ditolak 86,5% |
| Galeri 3 foto/individu, ambang 0,7 | terdaftar benar 86,9%, individu baru ditolak 95,0% |

Catatan: ATRW kemungkinan termasuk data latih MegaDescriptor, jadi angka ini optimistis. Corak harimau sangat
khas; untuk orangutan/gajah/badak akurasinya perlu diukur dengan foto individu nyata.

## Pengukuran ulang dengan kode produksi (2026-11-22)

Diukur ulang memakai modul yang benar-benar dipakai ai-service (`audio_pipeline.classify`, `vision.VisionModel`,
`reid.ReidModel`), bukan skrip latihan. Skrip: `eval_*` di folder ini.

Suara (2.548 klip uji, 0,3 detik/klip):

| Kategori | Tertangkap (recall) | Presisi |
|---|---|---|
| Tembakan | 74/87 (85%) | 74/99 (75%) |
| Gergaji mesin | 14/16 (88%) | 14/14 (100%) |
| Api | 6/8 (75%) | 6/8 (75%) |
| Mesin | 8/8 (100%) | 8/10 (80%) |
| Kendaraan | 17/24 (71%) | 17/24 (71%) |
| Anjing | 8/8 (100%) | 8/9 (89%) |
| Suara manusia | 60/64 (94%) | 60/66 (91%) |
| Suara satwa | 268/282 (95%) | 268/299 (90%) |
| Suara alam | 1.994/2.051 (97%) | 1.994/2.018 (99%) |

Tembakan per jarak (Lund): 10 m 21/21, 50 m 22/22, 150 m 11/12, 350 m 7/7. Kelas ESC-50 yang paling lemah:
kicau burung 2/8 (sering terbaca suara alam), pesawat 3/8, angin 4/8.

Kamera jebak (110 foto SWG): satwa terdeteksi 79/90 (88%), spesies tepat 52/90 (58%), benar minimal di tingkat
genus/famili 70/90 (78%), foto kosong 20/20 benar.

Re-identifikasi (ATRW, rata-rata 5 pembagian acak, galeri 3 foto/individu, ambang 0,6): dikenali benar 93,0%,
salah individu 3,4%, individu baru ditolak 86,2%.

## Pengenalan wajah staf (2026-11-23)

Model: **YuNet** (deteksi wajah, MIT) + **SFace** (embedding wajah 128-d, Apache-2.0, dilatih pada jutaan wajah;
akurasi LFW ±99,4%), model resmi OpenCV. Wajah staf "dilatih" dengan **mendaftarkan embedding semua fotonya ke galeri**
(`staff_faces`); melatih ulang jaringan dari 19 foto akan overfit dan tidak bisa dipercaya.

Data: dataset staf (`face_farrellino` 11 foto, `face_nuevalen` 8 foto, semua wajah terdeteksi) dan **LFW** (mirror resmi
scikit-learn, checksum diverifikasi) untuk ribuan wajah "orang lain". Skrip: `embed_staff_faces.py`, `eval_faces.py`.

Simulasi LFW (200 percobaan, 2 identitas didaftarkan sebagai staf dengan 8 foto, 220 penyusup per percobaan):

| Ambang | Staf dikenali benar | Staf tertukar | Orang lain lolos sebagai staf |
|---|---|---|---|
| 0,30 | 99,7% | 0% | 7,90% |
| 0,363 (rekomendasi OpenCV) | 99,7% | 0% | 1,14% |
| **0,40 (dipakai)** | **99,7%** | **0%** | **0,32%** |
| 0,45 | 99,7% | 0% | 0,03% |

Staf asli (leave-one-out, 19 foto) melawan 2.500 wajah LFW:

| Ambang | Dikenali benar | Tertukar | Orang lain lolos |
|---|---|---|---|
| 0,30 | 18/19 | 0 | 69/2.500 (2,8%) |
| **0,40** | **16/19** | **0** | **1/2.500 (0,04%)** |

Tiga foto yang gagal di 0,40 adalah foto Farrellino dengan skor deteksi wajah rendah (0,70–0,78, sudut/cahaya);
kemiripannya 0,28–0,35 ke foto Farrellino lain dan ≤ 0,21 ke staf lain, jadi tidak pernah tertukar. Karena itu
dipakai dua tingkat: **≥ 0,40 = staf (bernama)**, **0,30–0,40 = "Kemungkinan <nama> (perlu verifikasi)"** yang tetap
dihitung tidak dikenal, **lainnya = "Tidak dikenal / pengunjung"**. Augmentasi cermin (flip) diuji dan tidak membantu.

Uji end-to-end (foto yang tidak ada di galeri): foto Farrellino → Farrellino (0,92); foto Nuevalen dengan 2 orang di
latar → Nuevalen (0,53) + 2 tidak dikenal; 2 wajah LFW → tidak dikenal (0,08 / 0,13); video berisi wajah staf → staf.

## Audio pada video & tembakan beruntun

Trek suara video dianalisis dengan pipeline suara yang sama. Ditemukan saat uji: **tiga tembakan beruntun** terbaca
"suara manusia", karena pola impulsif berulang mirip tepuk tangan (tidak ada data latih tembakan beruntun). Membatalkan
veto suara manusia bila detektor tembakan yakin ≥ 0,9 tidak menambah tembakan tertangkap di data uji, tetapi membuat
15/64 (validasi) suara manusia impulsif terbaca tembakan. Kompromi yang dipakai: kategori tetap suara manusia, tetapi
bila detektor tembakan ≥ 0,9 hasil ditandai **`possible_gunshot` ("kemungkinan tembakan beruntun")**, ancaman `medium`
(notifikasi petugas, tanpa insiden otomatis). Akibatnya sebagian tepuk tangan juga ikut ditandai.

## Belum ada model terlatih

Kondisi fisik (sehat/terluka/kurus), perilaku, dan tanda senjata/jerat/penebangan/api pada foto belum punya model
terlatih publik untuk satwa Sumatra. Bagian ini memakai Groq vision dengan konteks hasil deteksi di atas, dan
hasilnya wajib diverifikasi petugas. Data verifikasi yang terkumpul bisa dipakai melatih model sendiri nanti.
