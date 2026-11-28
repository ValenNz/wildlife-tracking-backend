<?php

namespace App\Http\Controllers\Api;

use App\Models\AiPrediction;
use App\Models\GuideShift;
use App\Models\Notification;
use App\Models\TicketPrice;
use App\Models\TourGuide;
use App\Models\TourReview;
use App\Models\TourRoute;
use App\Models\User;
use App\Services\Tourism\BookingService;
use App\Services\Tourism\MidtransService;
use App\Services\Tourism\PricingService;
use App\Services\Tourism\TourismClock;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TourismCatalogController extends ApiController
{
    public function __construct(private BookingService $bookings, private PricingService $pricing) {}

    public function routes()
    {
        $routes = TourRoute::where('is_active', true)
            ->withAvg(['reviews' => fn ($q) => $q->where('is_visible', true)], 'route_rating')
            ->withCount(['reviews' => fn ($q) => $q->where('is_visible', true)])
            ->orderBy('name')
            ->get();

        return $this->list($routes);
    }

    public function showRoute(string $slug)
    {
        $route = TourRoute::where('slug', $slug)->where('is_active', true)
            ->withAvg(['reviews' => fn ($q) => $q->where('is_visible', true)], 'route_rating')
            ->withCount(['reviews' => fn ($q) => $q->where('is_visible', true)])
            ->firstOrFail();

        $reviews = TourReview::where('route_id', $route->route_id)->where('is_visible', true)
            ->with(['user:user_id,full_name', 'guide:guide_id,display_name'])
            ->latest()->limit(20)->get()
            ->map(fn (TourReview $r) => [
                'review_id' => $r->review_id,
                'reviewer' => $this->shortName($r->user?->full_name),
                'route_rating' => $r->route_rating,
                'guide_rating' => $r->guide_rating,
                'guide' => $r->guide?->display_name,
                'comment' => $r->comment,
                'created_at' => $r->created_at?->toIso8601String(),
            ]);

        return $this->success([
            'route' => $route,
            'upcoming_shifts' => $this->presentShifts(
                GuideShift::with('guide')->where('route_id', $route->route_id)
                    ->where('status', 'scheduled')->whereDate('shift_date', '>=', TourismClock::today())
                    ->orderBy('shift_date')->orderBy('start_time')->limit(30)->get()
            ),
            'reviews' => $reviews,
        ]);
    }

    public function guides()
    {
        $guides = TourGuide::where('is_active', true)
            ->withAvg(['reviews' => fn ($q) => $q->where('is_visible', true)], 'guide_rating')
            ->withCount(['reviews' => fn ($q) => $q->where('is_visible', true)])
            ->orderBy('display_name')->get();

        return $this->list($guides);
    }

    public function showGuide(int $id)
    {
        $guide = TourGuide::where('is_active', true)
            ->withAvg(['reviews' => fn ($q) => $q->where('is_visible', true)], 'guide_rating')
            ->withCount(['reviews' => fn ($q) => $q->where('is_visible', true)])
            ->findOrFail($id);

        return $this->success([
            'guide' => $guide,
            'upcoming_shifts' => $this->presentShifts(
                GuideShift::with('route')->where('guide_id', $id)->where('status', 'scheduled')
                    ->whereDate('shift_date', '>=', TourismClock::today())->orderBy('shift_date')->limit(30)->get()
            ),
        ]);
    }

    public function guidePhoto(int $id)
    {
        $guide = TourGuide::where('is_active', true)->findOrFail($id);
        abort_unless($guide->photo_path && Storage::disk('local')->exists($guide->photo_path), 404);

        return Storage::disk('local')->response($guide->photo_path, null, [
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function schedule(Request $request)
    {
        $data = $request->validate([
            'from' => 'sometimes|date',
            'to' => 'sometimes|date|after_or_equal:from',
            'route_id' => 'sometimes|integer',
            'guide_id' => 'sometimes|integer',
        ]);
        $this->bookings->expireStale();

        $from = $data['from'] ?? TourismClock::today();
        $to = $data['to'] ?? TourismClock::now()->addDays(13)->toDateString();
        if (now()->parse($from)->diffInDays(now()->parse($to)) > 62) {
            return $this->error('Rentang jadwal maksimal 62 hari.', 422);
        }

        $shifts = GuideShift::with(['guide', 'route'])
            ->where('status', 'scheduled')
            ->whereDate('shift_date', '>=', max($from, TourismClock::today()))
            ->whereDate('shift_date', '<=', $to)
            ->when($data['route_id'] ?? null, fn ($q, $id) => $q->where('route_id', $id))
            ->when($data['guide_id'] ?? null, fn ($q, $id) => $q->where('guide_id', $id))
            ->whereHas('guide', fn ($q) => $q->where('is_active', true))
            ->whereHas('route', fn ($q) => $q->where('is_active', true))
            ->orderBy('shift_date')->orderBy('start_time')
            ->get();

        return $this->list($this->presentShifts($shifts));
    }

    public function prices()
    {
        return $this->list(TicketPrice::where('is_active', true)->orderBy('category')->orderBy('amount')->get());
    }

    public function quote(Request $request)
    {
        $data = $request->validate([
            'route_id' => 'required|integer|exists:tour_routes,route_id',
            'visit_date' => 'required|date|after_or_equal:'.TourismClock::today(),
            'wni_count' => 'sometimes|integer|min:0|max:50',
            'wna_count' => 'sometimes|integer|min:0|max:50',
            'student_count' => 'sometimes|integer|min:0|max:50',
            'drone_count' => 'sometimes|integer|min:0|max:5',
            'porter_count' => 'sometimes|integer|min:0|max:20',
        ]);
        $route = TourRoute::where('is_active', true)->findOrFail($data['route_id']);

        return $this->success($this->pricing->quote(
            $route,
            $data['visit_date'],
            (int) ($data['wni_count'] ?? 0),
            (int) ($data['wna_count'] ?? 0),
            (int) ($data['student_count'] ?? 0),
            (int) ($data['drone_count'] ?? 0),
            (int) ($data['porter_count'] ?? 0),
        ));
    }

    public function routeSafety(string $slug)
    {
        $route = TourRoute::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $aiAlerts = AiPrediction::query()
            ->whereIn('threat_level', ['medium', 'high', 'critical'])
            ->orderByDesc('processed_at')
            ->orderByDesc('prediction_id')
            ->limit(5)
            ->get()
            ->map(fn (AiPrediction $p) => [
                'prediction_id' => $p->prediction_id,
                'threat_level' => $p->threat_level,
                'label' => $p->sound_label ?: ($p->detected_label ?: ($p->sound_category ?: 'Anomali Jalur')),
                'confidence' => $p->confidence !== null ? round((float) $p->confidence * 100, 1) : null,
                'summary' => $p->ai_summary ?: 'Terdeteksi aktivitas berisiko oleh sensor AI di sekitar koridor TNGL.',
                'detected_at' => $p->processed_at?->toIso8601String(),
            ])
            ->values();

        $trailStatus = 'aman';
        if ($aiAlerts->contains(fn ($a) => $a['threat_level'] === 'critical')) {
            $trailStatus = 'darurat';
        } elseif ($aiAlerts->isNotEmpty()) {
            $trailStatus = 'waspada';
        }

        return $this->success([
            'route_id' => $route->route_id,
            'slug' => $route->slug,
            'name' => $route->name,
            'trail_status' => $trailStatus,
            'porter_fee_per_day' => (int) ($route->porter_fee_per_day ?? 150000),
            'emergency_contacts' => $this->resolvedEmergencyContacts($route),
            'post_details' => $this->resolvedPostDetails($route),
            'ai_emergencies' => $aiAlerts,
        ]);
    }

    public function sendEmergencyAlert(Request $request, string $slug)
    {
        $route = TourRoute::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $data = $request->validate([
            'pos_code' => 'required|string|max:30',
            'emergency_type' => ['required', Rule::in(['cedera', 'tersesat', 'satwa_agresif', 'cuaca_ekstrem', 'kebakaran', 'lainnya'])],
            'description' => 'required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $alertCode = 'SOS-'.strtoupper(Str::random(6));
        $reporter = $request->user()?->full_name ?? 'Pengunjung';
        $coords = isset($data['latitude'], $data['longitude'])
            ? sprintf(' (GPS: %.5f, %.5f)', (float) $data['latitude'], (float) $data['longitude'])
            : '';
        $cleanDesc = trim(strip_tags($data['description']));

        $recipients = User::query()
            ->whereIn('status', ['aktif', 'active'])
            ->whereHas('role', fn ($q) => $q->whereIn('name_role', ['administrator_sistem', 'konservasionis_lapangan']))
            ->pluck('user_id');

        $notified = 0;
        foreach ($recipients as $userId) {
            Notification::create([
                'user_id' => $userId,
                'notification_type' => 'risk_zone',
                'message' => "[{$alertCode}] DARURAT {$route->name} ({$data['pos_code']}) · Pelapor: {$reporter} · Jenis: {$data['emergency_type']} · {$cleanDesc}{$coords}",
                'is_read' => false,
                'created_at' => now(),
            ]);
            $notified++;
        }

        $contacts = $this->resolvedEmergencyContacts($route);

        return $this->success([
            'alert_code' => $alertCode,
            'route' => $route->name,
            'pos_code' => $data['pos_code'],
            'emergency_type' => $data['emergency_type'],
            'trail_status' => 'darurat',
            'notified_rangers' => $notified,
            'nearest_contact' => $contacts[0] ?? null,
            'sent_at' => now()->toIso8601String(),
        ], 'Sinyal darurat instan berhasil dikirim ke posko & petugas lapangan.', 201);
    }

    private function resolvedEmergencyContacts(TourRoute $route): array
    {
        if (is_array($route->emergency_contacts) && count($route->emergency_contacts) > 0) {
            return $route->emergency_contacts;
        }

        return [
            [
                'name' => "Posko Siaga {$route->entry_point}",
                'role' => 'Komando Resort & Polhut BBTNGL',
                'phone' => '+62-61-8821-0112',
                'radio_channel' => 'VHF 156.800 MHz (Ch. 16)',
                'available' => '24 Jam',
                'location' => $route->meeting_point,
                'notes' => 'Prioritas pertama untuk evakuasi jalur dan koordinasi ranger.',
            ],
            [
                'name' => 'Tim SAR & Basarnas Sumut/Aceh',
                'role' => 'Evakuasi Medis & Pencarian',
                'phone' => '115',
                'radio_channel' => 'VHF 155.250 MHz',
                'available' => '24 Jam',
                'location' => 'Pos SAR Terdekat TNGL',
                'notes' => 'Hubungi bila terjadi cedera berat atau peserta terpisah dari rombongan.',
            ],
            [
                'name' => 'Puskesmas & Klinik Evakuasi',
                'role' => 'Pertolongan Medis Darurat',
                'phone' => '119',
                'radio_channel' => null,
                'available' => '24 Jam',
                'location' => "Kecamatan {$route->entry_point}",
                'notes' => 'Penanganan awal gigitan satwa, hipotermia, dan trauma fisik.',
            ],
        ];
    }

    private function resolvedPostDetails(TourRoute $route): array
    {
        if (is_array($route->post_details) && count($route->post_details) > 0) {
            return $route->post_details;
        }

        $totalKm = (float) ($route->distance_km ?: 6);
        $waypoints = is_array($route->waypoints) ? $route->waypoints : [];
        $startElev = isset($waypoints[0][2]) ? (int) $waypoints[0][2] : 120;
        $peakElev = count($waypoints) > 0
            ? (int) max(array_map(fn ($w) => (int) ($w[2] ?? $startElev), $waypoints))
            : $startElev + 180;
        $midElev = (int) round(($startElev + $peakElev) / 2);

        return [
            [
                'code' => 'POS-0',
                'name' => 'Basecamp & Gerbang Registrasi',
                'elevation_m' => $startElev,
                'distance_km' => 0.0,
                'water_source' => 'Air bersih & isi ulang minum tersedia',
                'signal' => '4G / Wi-Fi Posko',
                'capacity' => '30 orang',
                'facilities' => ['Registrasi QR', 'P3K Lengkap', 'Toilet', 'Briefing Shelter'],
                'notes' => "Titik kumpul resmi di {$route->meeting_point}.",
            ],
            [
                'code' => 'POS-1',
                'name' => 'Pos Pantau Koridor Hutan',
                'elevation_m' => $midElev,
                'distance_km' => round($totalKm * 0.45, 1),
                'water_source' => 'Mata air musiman (wajib filter/rebus)',
                'signal' => 'Sinyal seluler lemah · Radio VHF stabil',
                'capacity' => '16 orang',
                'facilities' => ['Shelter Kayu', 'Kotak P3K', 'Relay Radio'],
                'notes' => 'Titik istirahat pertama & pengecekan logistik rombongan.',
            ],
            [
                'code' => 'POS-2',
                'name' => 'Titik Puncak / Observasi Utama',
                'elevation_m' => $peakElev,
                'distance_km' => round($totalKm, 1),
                'water_source' => 'Tidak ada sumber air (bawa cadangan minimal 1L)',
                'signal' => 'Radio VHF 156.800 MHz',
                'capacity' => '12 orang',
                'facilities' => ['Area Observasi', 'Penanda Evakuasi', 'Sensor AI Jalur'],
                'notes' => 'Jaga jarak aman minimal 10 meter dari satwa liar dan hindari suara keras.',
            ],
        ];
    }

    public function paymentInfo()
    {
        $config = config('services.tourism');

        $simulatorEnabled = ! app()->isProduction() && (bool) ($config['payment_simulator'] ?? false);

        return $this->success([
            'bank_transfer' => [
                'bank_name' => $config['bank_name'] ?: null,
                'account_number' => $config['bank_account_number'] ?: null,
                'account_holder' => $config['bank_account_holder'] ?: null,
            ],
            'qris_merchant_name' => $config['qris_merchant_name'] ?: null,
            'payment_window_minutes' => (int) $config['payment_window_minutes'],
            'gateway_enabled' => ($config['webhook_secret'] ?? '') !== '' || MidtransService::enabled() || $simulatorEnabled,
            'midtrans_enabled' => MidtransService::enabled(),
            'simulator_enabled' => $simulatorEnabled,
        ]);
    }

    private function presentShifts($shifts): array
    {
        return $shifts->map(fn (GuideShift $s) => [
            'shift_id' => $s->shift_id,
            'shift_date' => $s->shift_date->toDateString(),
            'shift_type' => $s->shift_type,
            'start_time' => substr((string) $s->start_time, 0, 5),
            'end_time' => substr((string) $s->end_time, 0, 5),
            'capacity' => $s->capacity,
            'remaining' => $this->bookings->remainingCapacity($s),
            'guide' => $s->relationLoaded('guide') && $s->guide ? [
                'guide_id' => $s->guide->guide_id,
                'display_name' => $s->guide->display_name,
                'favorite_animal' => $s->guide->favorite_animal,
                'languages' => $s->guide->languages,
                'has_photo' => $s->guide->has_photo,
            ] : null,
            'route' => $s->relationLoaded('route') && $s->route ? [
                'route_id' => $s->route->route_id,
                'slug' => $s->route->slug,
                'name' => $s->route->name,
                'entry_point' => $s->route->entry_point,
            ] : null,
        ])->values()->all();
    }

    private function shortName(?string $name): string
    {
        if (! $name) {
            return 'Pengunjung';
        }
        $parts = preg_split('/\s+/', trim($name));

        return count($parts) > 1 ? $parts[0].' '.mb_substr(end($parts), 0, 1).'.' : $parts[0];
    }
}
