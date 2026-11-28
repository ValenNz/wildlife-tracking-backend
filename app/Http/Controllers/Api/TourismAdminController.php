<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\GuideShift;
use App\Models\Payment;
use App\Models\PublicHoliday;
use App\Models\TicketPrice;
use App\Models\TourGuide;
use App\Models\TourReview;
use App\Models\TourRoute;
use App\Services\Tourism\BookingService;
use App\Services\Tourism\TourismClock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TourismAdminController extends ApiController
{
    public function __construct(private BookingService $bookings) {}


    public function routes()
    {
        return $this->list(TourRoute::withCount('shifts')->orderBy('name'));
    }

    public function showRoute(int $id)
    {
        return $this->success(TourRoute::findOrFail($id));
    }

    public function storeRoute(Request $request)
    {
        $data = $this->validateRoute($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        return $this->success(TourRoute::create($data), 'Rute dibuat', 201);
    }

    public function updateRoute(Request $request, int $id)
    {
        $route = TourRoute::findOrFail($id);
        $route->update($this->validateRoute($request, $route));

        return $this->success($route->fresh(), 'Rute diperbarui');
    }

    public function destroyRoute(int $id)
    {
        $route = TourRoute::findOrFail($id);
        if (Booking::where('route_id', $id)->exists()) {
            $route->update(['is_active' => false]);

            return $this->success($route, 'Rute sudah pernah dipesan, jadi dinonaktifkan (bukan dihapus).');
        }
        $route->delete();

        return $this->success(null, 'Rute dihapus');
    }

    private function validateRoute(Request $request, ?TourRoute $route = null): array
    {
        $required = $route ? 'sometimes' : 'required';

        return $request->validate([
            'code' => [$required, 'string', 'max:30', Rule::unique('tour_routes', 'code')->ignore($route?->route_id, 'route_id')],
            'name' => "{$required}|string|max:150",
            'entry_point' => "{$required}|string|max:100",
            'summary' => "{$required}|string|max:500",
            'description' => "{$required}|string|max:5000",
            'difficulty' => [$required, Rule::in(['mudah', 'sedang', 'sulit'])],
            'duration_hours' => "{$required}|integer|min:1|max:500",
            'duration_days' => "{$required}|integer|min:1|max:30",
            'distance_km' => 'nullable|numeric|min:0|max:500',
            'max_group_size' => "{$required}|integer|min:1|max:50",
            'min_age' => 'sometimes|integer|min:0|max:80',
            'meeting_point' => "{$required}|string|max:200",
            'meeting_lat' => 'nullable|numeric|between:-90,90',
            'meeting_lon' => 'nullable|numeric|between:-180,180',
            'start_time' => "{$required}|date_format:H:i",
            'guide_fee_per_person' => "{$required}|integer|min:0|max:100000000",
            'itinerary' => "{$required}|array|max:30",
            'itinerary.*.time' => 'required|string|max:30',
            'itinerary.*.title' => 'required|string|max:100',
            'itinerary.*.description' => 'nullable|string|max:500',
            'includes' => "{$required}|array|max:30",
            'includes.*' => 'string|max:200',
            'bring_list' => "{$required}|array|max:30",
            'bring_list.*' => 'string|max:200',
            'rules' => "{$required}|array|max:30",
            'rules.*' => 'string|max:200',
            'wildlife' => "{$required}|array|max:30",
            'wildlife.*' => 'string|max:100',
            'waypoints' => 'nullable|array|max:500',
            'waypoints.*' => 'array|size:3',
            'waypoints.*.*' => 'numeric',
            'zone_id' => 'nullable|integer|exists:geo_zones,zone_id',
            'is_active' => 'sometimes|boolean',
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'rute';
        $slug = $base;
        for ($i = 2; TourRoute::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }


    public function guides()
    {
        return $this->list(TourGuide::withCount('shifts')->orderBy('display_name')->get()->each->makeVisible('user_id'));
    }

    public function storeGuide(Request $request)
    {
        return $this->success(TourGuide::create($this->validateGuide($request)), 'Pemandu ditambahkan', 201);
    }

    public function updateGuide(Request $request, int $id)
    {
        $guide = TourGuide::findOrFail($id);
        $guide->update($this->validateGuide($request, $guide));

        return $this->success($guide->fresh(), 'Profil pemandu diperbarui');
    }

    public function destroyGuide(int $id)
    {
        $guide = TourGuide::findOrFail($id);
        if (Booking::whereIn('shift_id', $guide->shifts()->pluck('shift_id'))->exists()) {
            $guide->update(['is_active' => false]);

            return $this->success($guide, 'Pemandu sudah punya riwayat pemesanan, jadi dinonaktifkan.');
        }
        if ($guide->photo_path) {
            Storage::disk('local')->delete($guide->photo_path);
        }
        $guide->delete();

        return $this->success(null, 'Pemandu dihapus');
    }

    public function uploadGuidePhoto(Request $request, int $id)
    {
        $guide = TourGuide::findOrFail($id);
        $request->validate(['file' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120']);

        if ($guide->photo_path) {
            Storage::disk('local')->delete($guide->photo_path);
        }
        $guide->photo_path = $request->file('file')->store('guide-photos', 'local');
        $guide->save();

        return $this->success($guide->fresh(), 'Foto pemandu diperbarui');
    }

    private function validateGuide(Request $request, ?TourGuide $guide = null): array
    {
        $required = $guide ? 'sometimes' : 'required';

        return $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,user_id', Rule::unique('tour_guides', 'user_id')->ignore($guide?->guide_id, 'guide_id')],
            'display_name' => "{$required}|string|max:100",
            'bio' => "{$required}|string|max:2000",
            'languages' => "{$required}|array|min:1|max:10",
            'languages.*' => 'string|max:50',
            'specialties' => "{$required}|array|max:10",
            'specialties.*' => 'string|max:100',
            'favorite_animal' => "{$required}|string|max:100",
            'favorite_animal_reason' => 'nullable|string|max:255',
            'years_experience' => 'sometimes|integer|min:0|max:70',
            'certification' => 'nullable|string|max:150',
            'fun_fact' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);
    }


    public function shifts(Request $request)
    {
        $data = $request->validate([
            'from' => 'sometimes|date',
            'to' => 'sometimes|date|after_or_equal:from',
            'guide_id' => 'sometimes|integer',
            'route_id' => 'sometimes|integer',
        ]);

        $shifts = GuideShift::with(['guide:guide_id,display_name', 'route:route_id,name'])
            ->whereDate('shift_date', '>=', $data['from'] ?? TourismClock::today())
            ->whereDate('shift_date', '<=', $data['to'] ?? TourismClock::now()->addDays(30)->toDateString())
            ->when($data['guide_id'] ?? null, fn ($q, $id) => $q->where('guide_id', $id))
            ->when($data['route_id'] ?? null, fn ($q, $id) => $q->where('route_id', $id))
            ->orderBy('shift_date')->orderBy('start_time')->get()
            ->map(fn (GuideShift $s) => $s->toArray() + ['remaining' => $this->bookings->remainingCapacity($s)]);

        return $this->list($shifts);
    }

    public function storeShift(Request $request)
    {
        $data = $this->validateShift($request);

        return $this->success(GuideShift::create($data)->load(['guide', 'route']), 'Shift dibuat', 201);
    }

    public function updateShift(Request $request, int $id)
    {
        $shift = GuideShift::findOrFail($id);
        $data = $this->validateShift($request, $shift);

        $booked = $shift->capacity - $this->bookings->remainingCapacity($shift);
        if (isset($data['capacity']) && $data['capacity'] < $booked) {
            return $this->error("Kapasitas tidak boleh kurang dari {$booked} peserta yang sudah memesan.", 422);
        }
        if (($data['status'] ?? null) === 'cancelled' && $booked > 0) {
            return $this->error('Shift sudah punya pemesanan. Batalkan atau pindahkan pemesanan terlebih dahulu.', 409);
        }
        $shift->update($data);

        return $this->success($shift->fresh(['guide', 'route']), 'Shift diperbarui');
    }

    public function destroyShift(int $id)
    {
        $shift = GuideShift::findOrFail($id);
        if ($shift->bookings()->exists()) {
            return $this->error('Shift sudah punya pemesanan; ubah status menjadi dibatalkan bila perlu.', 409);
        }
        $shift->delete();

        return $this->success(null, 'Shift dihapus');
    }

    private function validateShift(Request $request, ?GuideShift $shift = null): array
    {
        $required = $shift ? 'sometimes' : 'required';
        $data = $request->validate([
            'guide_id' => "{$required}|integer|exists:tour_guides,guide_id",
            'route_id' => "{$required}|integer|exists:tour_routes,route_id",
            'shift_date' => "{$required}|date|after_or_equal:".TourismClock::today(),
            'shift_type' => [$required, Rule::in(GuideShift::TYPES)],
            'start_time' => "{$required}|date_format:H:i",
            'end_time' => "{$required}|date_format:H:i",
            'capacity' => "{$required}|integer|min:1|max:50",
            'status' => ['sometimes', Rule::in(['scheduled', 'cancelled'])],
        ]);

        $guideId = $data['guide_id'] ?? $shift?->guide_id;
        $date = $data['shift_date'] ?? $shift?->shift_date?->toDateString();
        $type = $data['shift_type'] ?? $shift?->shift_type;
        $clash = GuideShift::where('guide_id', $guideId)->whereDate('shift_date', $date)->where('shift_type', $type)
            ->when($shift, fn ($q) => $q->where('shift_id', '!=', $shift->shift_id))->exists();
        if ($clash) {
            abort(409, 'Pemandu sudah punya shift yang sama pada tanggal itu.');
        }

        return $data;
    }


    public function prices()
    {
        return $this->list(TicketPrice::orderBy('category')->orderBy('code'));
    }

    public function updatePrice(Request $request, int $id)
    {
        $price = TicketPrice::findOrFail($id);
        $price->update($request->validate([
            'name' => 'sometimes|string|max:150',
            'amount' => 'sometimes|integer|min:0|max:100000000',
            'min_quantity' => 'sometimes|integer|min:1|max:100',
            'legal_basis' => 'nullable|string|max:150',
            'is_active' => 'sometimes|boolean',
        ]));

        return $this->success($price->fresh(), 'Tarif diperbarui');
    }

    public function holidays()
    {
        return $this->list(PublicHoliday::whereDate('holiday_date', '>=', today()->startOfYear())->orderBy('holiday_date'));
    }

    public function storeHoliday(Request $request)
    {
        $data = $request->validate([
            'holiday_date' => 'required|date|unique:public_holidays,holiday_date',
            'name' => 'required|string|max:150',
        ]);

        return $this->success(PublicHoliday::create($data), 'Hari libur ditambahkan', 201);
    }

    public function destroyHoliday(int $id)
    {
        PublicHoliday::findOrFail($id)->delete();

        return $this->success(null, 'Hari libur dihapus');
    }


    public function bookings(Request $request)
    {
        $data = $request->validate([
            'status' => 'sometimes|string',
            'visit_date' => 'sometimes|date',
            'search' => 'sometimes|string|max:50',
        ]);
        $this->bookings->expireStale();

        return $this->list(Booking::with(['route:route_id,name', 'shift.guide:guide_id,display_name', 'user:user_id,full_name,email', 'payments'])
            ->when($data['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($data['visit_date'] ?? null, fn ($q, $d) => $q->whereDate('visit_date', $d))
            ->when($data['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('booking_code', 'like', '%'.$s.'%')->orWhere('contact_name', 'like', '%'.$s.'%')))
            ->orderByDesc('created_at'));
    }

    public function showBooking(string $code)
    {
        return $this->success(Booking::with(['route', 'shift.guide', 'user:user_id,full_name,email,phone', 'participants', 'payments.verifier:user_id,full_name', 'review'])
            ->where('booking_code', $code)->firstOrFail());
    }

    public function cancelBooking(string $code)
    {
        $booking = $this->bookings->cancel(Booking::where('booking_code', $code)->firstOrFail(), byStaff: true);

        return $this->success($booking, 'Pemesanan dibatalkan');
    }

    public function payments(Request $request)
    {
        $status = $request->validate(['status' => 'sometimes|string'])['status'] ?? null;

        return $this->list(Payment::with(['booking:booking_id,booking_code,contact_name,visit_date,status,total_amount', 'verifier:user_id,full_name'])
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('updated_at'));
    }

    public function paymentProof(int $id)
    {
        $payment = Payment::findOrFail($id);
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->response($payment->proof_path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function verifyPayment(Request $request, int $id)
    {
        $data = $request->validate([
            'approve' => 'required|boolean',
            'reason' => 'required_if:approve,false|nullable|string|max:255',
        ]);
        $payment = Payment::findOrFail($id);

        if ($data['approve']) {
            if ($payment->status !== 'awaiting_verification') {
                return $this->error('Pembayaran tidak sedang menunggu verifikasi.', 409);
            }
            $booking = $this->bookings->markPaid($payment, $request->user());

            return $this->success($booking->load('payments'), 'Pembayaran diterima, tiket terbit.');
        }

        return $this->success($this->bookings->rejectPayment($payment, $request->user(), $data['reason']), 'Bukti pembayaran ditolak.');
    }


    public function reviews()
    {
        return $this->list(TourReview::with(['route:route_id,name', 'guide:guide_id,display_name', 'user:user_id,full_name'])
            ->orderByDesc('created_at'));
    }

    public function updateReviewVisibility(Request $request, int $id)
    {
        $review = TourReview::findOrFail($id);
        $review->update($request->validate(['is_visible' => 'required|boolean']));

        return $this->success($review, $review->is_visible ? 'Ulasan ditampilkan' : 'Ulasan disembunyikan');
    }
}
