<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Models\GuideShift;
use App\Models\PublicHoliday;
use App\Models\TicketPrice;
use App\Models\TourGuide;
use App\Models\TourRoute;
use App\Services\Tourism\TourismClock;
use Illuminate\Database\Seeder;

class TourismSeeder extends Seeder
{
    private const LEGAL_BASIS = 'PP 36/2024 (Kelas III)';

    public function run(): void
    {
        $this->prices();
        $this->holidays();
        $routes = $this->routes();
        $guides = $this->guides();
        $this->shifts($routes, $guides);
    }

    private function prices(): void
    {
        foreach ([
            ['entry_wni_weekday', 'Tiket masuk wisatawan nusantara (Senin-Sabtu)', 'entry', 'wni', 'weekday', 'orang/hari', 10000, 1],
            ['entry_wni_holiday', 'Tiket masuk wisatawan nusantara (Minggu & libur nasional)', 'entry', 'wni', 'holiday', 'orang/hari', 15000, 1],
            ['entry_student_weekday', 'Tiket masuk pelajar/mahasiswa, rombongan min. 5 orang (Senin-Sabtu)', 'entry', 'pelajar', 'weekday', 'orang/hari', 5000, 5],
            ['entry_student_holiday', 'Tiket masuk pelajar/mahasiswa, rombongan min. 5 orang (Minggu & libur nasional)', 'entry', 'pelajar', 'holiday', 'orang/hari', 7500, 5],
            ['entry_wna', 'Tiket masuk wisatawan mancanegara', 'entry', 'wna', 'any', 'orang/hari', 150000, 1],
            ['activity_drone', 'Penggunaan drone', 'activity', 'any', 'any', 'unit/hari', 2000000, 1],
        ] as [$code, $name, $category, $visitor, $day, $unit, $amount, $min]) {
            TicketPrice::updateOrCreate(['code' => $code], [
                'name' => $name, 'category' => $category, 'visitor_type' => $visitor, 'day_type' => $day,
                'unit' => $unit, 'amount' => $amount, 'min_quantity' => $min, 'legal_basis' => self::LEGAL_BASIS,
                'is_active' => true,
            ]);
        }
    }

    private function holidays(): void
    {
        $year = (int) now()->format('Y');
        foreach ([$year, $year + 1] as $y) {
            foreach ([
                ['01-01', 'Tahun Baru Masehi'],
                ['05-01', 'Hari Buruh Internasional'],
                ['06-01', 'Hari Lahir Pancasila'],
                ['08-17', 'Hari Kemerdekaan RI'],
                ['12-25', 'Hari Raya Natal'],
            ] as [$md, $name]) {
                PublicHoliday::updateOrCreate(['holiday_date' => "{$y}-{$md}"], ['name' => $name]);
            }
        }

        if (now()->year <= 2027) {
            foreach ([
                ['2027-01-05', 'Isra Mikraj Nabi Muhammad SAW'],
                ['2027-02-06', 'Tahun Baru Imlek 2578 Kongzili'],
                ['2027-03-10', 'Hari Raya Idulfitri 1448 H (menunggu keputusan Menag)'],
                ['2027-03-11', 'Hari Raya Idulfitri 1448 H (menunggu keputusan Menag)'],
                ['2027-03-26', 'Wafat Yesus Kristus'],
                ['2027-03-28', 'Kebangkitan Yesus Kristus (Paskah)'],
                ['2027-05-06', 'Kenaikan Yesus Kristus'],
                ['2027-05-17', 'Hari Raya Iduladha 1448 H (menunggu keputusan Menag)'],
                ['2027-05-20', 'Hari Raya Waisak 2571 BE'],
                ['2027-06-06', 'Tahun Baru Islam 1449 H'],
                ['2027-08-15', 'Maulid Nabi Muhammad SAW'],
                ['2027-12-26', 'Isra Mikraj Nabi Muhammad SAW 1449 H'],
            ] as [$date, $name]) {
                PublicHoliday::updateOrCreate(['holiday_date' => $date], ['name' => $name]);
            }
        }
    }

    private function routes(): array
    {
        $zone = GeoZone::where('zone_name', 'Zona Konservasi Utara')->value('zone_id');
        $common = [
            'porter_fee_per_day' => 150000,
            'bring_list' => ['Sepatu trekking yang mencengkeram', 'Jas hujan atau ponco', 'Air minum minimal 1,5 liter', 'Obat pribadi', 'Lotion anti nyamuk', 'Kantong plastik untuk sampah sendiri'],
            'rules' => ['Jaga jarak minimal 10 meter dari satwa', 'Dilarang memberi makan satwa', 'Dilarang membawa pulang tumbuhan atau satwa', 'Dilarang menyalakan api di luar titik yang ditentukan', 'Sampah dibawa kembali', 'Ikuti arahan pemandu setiap saat'],
            'emergency_contacts' => [
                [
                    'name' => 'Posko Komando BBTNGL Bukit Lawang',
                    'role' => 'Koordinator Resort & Ranger Lapangan',
                    'phone' => '+62-61-8821-0112',
                    'radio_channel' => 'VHF 156.800 MHz (Ch. 16)',
                    'available' => '24 Jam',
                    'location' => 'Gerbang Utama Bukit Lawang, Langkat',
                    'notes' => 'Pusat koordinasi evakuasi jalur & laporan darurat satwa liar.',
                ],
                [
                    'name' => 'Basarnas / Tim SAR Medan-Langkat',
                    'role' => 'Pencarian & Evakuasi Medis Jalur',
                    'phone' => '115',
                    'radio_channel' => 'VHF 155.250 MHz',
                    'available' => '24 Jam',
                    'location' => 'Pos SAR Langkat',
                    'notes' => 'Penanganan darurat cedera berat, hipotermia, atau pendaki tersesat.',
                ],
                [
                    'name' => 'Puskesmas Bohorok & Klinik Evakuasi',
                    'role' => 'Layanan Medis & Ambulans',
                    'phone' => '119',
                    'radio_channel' => null,
                    'available' => '24 Jam',
                    'location' => 'Kec. Bohorok, Langkat',
                    'notes' => 'Tindakan awal trauma, alergi sengatan, dan dehidrasi.',
                ],
                [
                    'name' => 'Polhut Seksi Wilayah III Ketambe',
                    'role' => 'Pengamanan & Mitigasi Konflik Satwa',
                    'phone' => '+62-629-21455',
                    'radio_channel' => 'VHF 157.100 MHz',
                    'available' => '06:00–22:00 WIB',
                    'location' => 'Resort Ketambe, Aceh Tenggara',
                    'notes' => 'Kontak utama untuk jalur kawasan Ketambe dan Lembah Alas.',
                ],
            ],
        ];

        $routes = [
            'bukit-lawang-orangutan-trek' => [
                'code' => 'BL-01', 'name' => 'Trek Orangutan Bukit Lawang', 'entry_point' => 'Bukit Lawang, Langkat (Sumatera Utara)',
                'summary' => 'Jelajah hutan hujan setengah hari untuk melihat orangutan sumatera di habitat aslinya.',
                'description' => 'Rute paling populer di TNGL. Berjalan menyusuri tepi Sungai Bahorok lalu masuk hutan primer berbukit. Peluang bertemu orangutan sumatera, kedih (Thomas leaf monkey), dan rangkong cukup tinggi pada pagi hari.',
                'difficulty' => 'sedang', 'duration_hours' => 5, 'duration_days' => 1, 'distance_km' => 6.5, 'max_group_size' => 8, 'min_age' => 10,
                'meeting_point' => 'Pos Pemandu Bukit Lawang, dekat jembatan gantung Sungai Bahorok', 'meeting_lat' => 3.5527, 'meeting_lon' => 98.1186,
                'start_time' => '08:00', 'guide_fee_per_person' => 350000,
                'itinerary' => [
                    ['time' => '07:45', 'title' => 'Registrasi & briefing', 'description' => 'Pemeriksaan tiket, pembagian kelompok, dan aturan aman bertemu satwa.'],
                    ['time' => '08:00', 'title' => 'Masuk hutan', 'description' => 'Menyeberangi Sungai Bahorok dan mendaki jalur hutan.'],
                    ['time' => '09:30', 'title' => 'Pengamatan orangutan', 'description' => 'Mencari orangutan dan primata lain bersama pemandu.'],
                    ['time' => '11:00', 'title' => 'Istirahat & buah segar', 'description' => 'Istirahat di titik pandang hutan.'],
                    ['time' => '12:30', 'title' => 'Kembali ke desa', 'description' => 'Turun melalui jalur berbeda dan kembali ke pos.'],
                ],
                'includes' => ['Pemandu berlisensi', 'Buah dan air minum saat istirahat', 'Tiket masuk sesuai jenis pengunjung'],
                'wildlife' => ['Orangutan Sumatra', 'Kedih (Thomas leaf monkey)', 'Monyet ekor panjang', 'Rangkong', 'Biawak'],
                'waypoints' => [[3.5527, 98.1186, 110], [3.5551, 98.1140, 180], [3.5589, 98.1102, 260], [3.5611, 98.1150, 210], [3.5560, 98.1195, 120]],
            ],
            'bukit-lawang-jungle-camp' => [
                'code' => 'BL-02', 'name' => 'Jungle Camp 2 Hari 1 Malam Bukit Lawang', 'entry_point' => 'Bukit Lawang, Langkat (Sumatera Utara)',
                'summary' => 'Trekking dua hari dengan bermalam di kemah tepi sungai dan pulang dengan tubing.',
                'description' => 'Masuk lebih dalam ke hutan untuk pengalaman satwa yang lebih lengkap. Malam hari di kemah tepi sungai, hari kedua kembali ke desa dengan tubing (ban karet) menyusuri Sungai Bahorok bila arus aman.',
                'difficulty' => 'sulit', 'duration_hours' => 30, 'duration_days' => 2, 'distance_km' => 14, 'max_group_size' => 6, 'min_age' => 14,
                'meeting_point' => 'Pos Pemandu Bukit Lawang', 'meeting_lat' => 3.5527, 'meeting_lon' => 98.1186,
                'start_time' => '08:30', 'guide_fee_per_person' => 900000,
                'itinerary' => [
                    ['time' => 'Hari 1, 08:30', 'title' => 'Berangkat trekking', 'description' => 'Jalur hutan primer, pengamatan orangutan dan primata.'],
                    ['time' => 'Hari 1, 15:00', 'title' => 'Tiba di kemah', 'description' => 'Mandi di sungai, makan malam masakan lokal.'],
                    ['time' => 'Hari 1, 19:30', 'title' => 'Jelajah malam singkat', 'description' => 'Mengamati serangga dan satwa malam di sekitar kemah.'],
                    ['time' => 'Hari 2, 09:00', 'title' => 'Tubing pulang', 'description' => 'Kembali ke desa dengan ban karet bila arus aman; bila tidak, berjalan kaki.'],
                ],
                'includes' => ['Pemandu & porter', 'Tenda dan matras', 'Makan 3 kali', 'Perlengkapan tubing', 'Tiket masuk 2 hari'],
                'wildlife' => ['Orangutan Sumatra', 'Owa ungko', 'Siamang', 'Kedih (Thomas leaf monkey)', 'Rangkong badak'],
                'waypoints' => [[3.5527, 98.1186, 110], [3.5610, 98.1050, 320], [3.5702, 98.0985, 410], [3.5755, 98.1032, 250]],
            ],
            'tangkahan-river-elephant' => [
                'code' => 'TK-01', 'name' => 'Sungai & Patroli Gajah Tangkahan', 'entry_point' => 'Tangkahan, Langkat (Sumatera Utara)',
                'summary' => 'Menyusuri Sungai Batang Serangan dan melihat kegiatan gajah patroli bersama mahout.',
                'description' => 'Tangkahan dikenal dengan ekowisata berbasis masyarakat. Pengunjung mengikuti kegiatan memandikan gajah patroli (tanpa menunggang), lalu berjalan ke air terjun dan sumber air panas di tepi sungai.',
                'difficulty' => 'mudah', 'duration_hours' => 4, 'duration_days' => 1, 'distance_km' => 4, 'max_group_size' => 10, 'min_age' => 6,
                'meeting_point' => 'Pusat Informasi Ekowisata Tangkahan', 'meeting_lat' => 3.6833, 'meeting_lon' => 98.0667,
                'start_time' => '09:00', 'guide_fee_per_person' => 250000,
                'itinerary' => [
                    ['time' => '08:45', 'title' => 'Registrasi', 'description' => 'Pemeriksaan tiket dan pengenalan program gajah patroli.'],
                    ['time' => '09:00', 'title' => 'Memandikan gajah', 'description' => 'Membantu mahout memandikan gajah di sungai.'],
                    ['time' => '10:30', 'title' => 'Jalan ke air terjun', 'description' => 'Jalur tepi sungai menuju air terjun dan sumber air panas.'],
                    ['time' => '12:30', 'title' => 'Selesai', 'description' => 'Kembali ke pusat informasi.'],
                ],
                'includes' => ['Pemandu lokal', 'Kegiatan bersama mahout', 'Tiket masuk'],
                'wildlife' => ['Gajah Sumatra (gajah patroli)', 'Monyet ekor panjang', 'Elang', 'Kupu-kupu raja'],
                'waypoints' => [[3.6833, 98.0667, 140], [3.6851, 98.0705, 150], [3.6880, 98.0731, 175]],
            ],
            'ketambe-forest-trek' => [
                'code' => 'KT-01', 'name' => 'Hutan Riset Ketambe', 'entry_point' => 'Ketambe, Aceh Tenggara (Aceh)',
                'summary' => 'Trekking hutan dataran rendah yang tenang di sekitar Ketambe, Lembah Alas.',
                'description' => 'Rute untuk pengunjung yang ingin suasana lebih sepi. Hutan dataran rendah di sekitar Ketambe kaya primata dan burung. Stasiun riset sendiri tidak dibuka untuk umum; tur berjalan di jalur wisata yang ditentukan.',
                'difficulty' => 'sedang', 'duration_hours' => 6, 'duration_days' => 1, 'distance_km' => 8, 'max_group_size' => 6, 'min_age' => 12,
                'meeting_point' => 'Pos Ketambe, Jalan Kutacane-Blangkejeren', 'meeting_lat' => 3.6833, 'meeting_lon' => 97.65,
                'start_time' => '07:30', 'guide_fee_per_person' => 400000,
                'itinerary' => [
                    ['time' => '07:30', 'title' => 'Briefing', 'description' => 'Pemeriksaan tiket dan aturan jalur.'],
                    ['time' => '08:00', 'title' => 'Jalur hutan', 'description' => 'Pengamatan primata dan burung.'],
                    ['time' => '11:00', 'title' => 'Sungai Alas', 'description' => 'Istirahat makan siang di tepi sungai.'],
                    ['time' => '13:30', 'title' => 'Kembali', 'description' => 'Kembali ke pos Ketambe.'],
                ],
                'includes' => ['Pemandu berlisensi', 'Makan siang', 'Tiket masuk'],
                'wildlife' => ['Orangutan Sumatra', 'Siamang', 'Owa ungko', 'Rangkong', 'Kuau raja'],
                'waypoints' => [[3.6833, 97.6500, 400], [3.6871, 97.6455, 520], [3.6902, 97.6501, 450]],
            ],
        ];

        $models = [];
        foreach ($routes as $slug => $data) {
            $models[$slug] = TourRoute::updateOrCreate(['slug' => $slug], $data + $common + ['zone_id' => $zone, 'is_active' => true]);
        }

        return $models;
    }

    private function guides(): array
    {
        $guides = [];
        foreach ([
            ['Rahmat Ginting', 'Lahir dan besar di Bukit Lawang, sudah memandu sejak usia 19 tahun.', ['Indonesia', 'Inggris', 'Karo'], ['Orangutan', 'Tanaman obat hutan'], 'Orangutan Sumatra', 'Setiap individu punya kepribadian sendiri; saya hafal lebih dari 20 orangutan semi-liar.', 12, 'Pemandu Wisata Alam bersertifikat', 'Pernah menemani tim dokumenter satwa selama tiga minggu di hutan.'],
            ['Sari Purba', 'Lulusan biologi yang memilih kembali ke kampung untuk menjadi pemandu.', ['Indonesia', 'Inggris', 'Jerman'], ['Burung', 'Fotografi satwa'], 'Rangkong badak', 'Suara kepakan sayapnya terdengar dari jauh, seperti helikopter kecil.', 6, 'Pemandu Wisata Alam bersertifikat', 'Sudah mencatat 140 jenis burung di sekitar Bukit Lawang.'],
            ['Bang Udin Sembiring', 'Mantan mahout yang kini memandu ekowisata Tangkahan.', ['Indonesia', 'Inggris'], ['Gajah', 'Sungai & air terjun'], 'Gajah Sumatra', 'Gajah itu cerdas dan setia; saya bekerja dengan gajah patroli selama 9 tahun.', 15, 'Mahout & pemandu bersertifikat', 'Bisa mengenali tiap gajah hanya dari suara langkahnya.'],
            ['Nurul Aini', 'Pemandu dari Lembah Alas yang fokus pada wisata keluarga dan edukasi anak.', ['Indonesia', 'Inggris', 'Gayo'], ['Primata', 'Edukasi konservasi'], 'Siamang', 'Duet suara siamang di pagi hari adalah "alarm" hutan favorit saya.', 4, 'Pemandu Wisata Alam bersertifikat', 'Membuat kartu satwa bergambar untuk anak-anak peserta tur.'],
        ] as [$name, $bio, $languages, $specialties, $animal, $reason, $years, $cert, $fact]) {
            $guides[] = TourGuide::updateOrCreate(['display_name' => $name], [
                'bio' => $bio, 'languages' => $languages, 'specialties' => $specialties, 'favorite_animal' => $animal,
                'favorite_animal_reason' => $reason, 'years_experience' => $years, 'certification' => $cert,
                'fun_fact' => $fact, 'is_active' => true,
            ]);
        }

        return $guides;
    }

    private function shifts(array $routes, array $guides): void
    {
        $plan = [
            ['bukit-lawang-orangutan-trek', 0, 'pagi', '07:45', '13:00'],
            ['bukit-lawang-orangutan-trek', 1, 'siang', '12:30', '17:30'],
            ['bukit-lawang-jungle-camp', 1, 'pagi', '08:30', '17:00'],
            ['tangkahan-river-elephant', 2, 'pagi', '08:45', '13:00'],
            ['ketambe-forest-trek', 3, 'pagi', '07:30', '14:00'],
        ];

        for ($day = 0; $day <= 14; $day++) {
            $date = TourismClock::now()->addDays($day)->toDateString();
            foreach ($plan as $i => [$slug, $guideIndex, $type, $start, $end]) {
                if (($day + $i) % 5 === 0) {
                    continue;
                }
                $route = $routes[$slug];
                GuideShift::updateOrCreate(
                    ['guide_id' => $guides[$guideIndex]->guide_id, 'shift_date' => $date, 'shift_type' => $type],
                    ['route_id' => $route->route_id, 'start_time' => $start, 'end_time' => $end,
                        'capacity' => $route->max_group_size, 'status' => 'scheduled'],
                );
            }
        }
    }
}
