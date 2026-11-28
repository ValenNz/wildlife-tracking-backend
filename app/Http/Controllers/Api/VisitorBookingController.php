<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\TourReview;
use App\Services\Tourism\BookingService;
use App\Services\Tourism\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VisitorBookingController extends ApiController
{
    private const RELATIONS = ['route:route_id,slug,name,entry_point,meeting_point,start_time,duration_hours,duration_days,bring_list,rules',
        'shift.guide:guide_id,display_name,favorite_animal,languages', 'participants', 'payments', 'review'];

    public function __construct(private BookingService $bookings) {}

    public function index(Request $request)
    {
        $this->bookings->expireStale();

        return $this->list(Booking::with(self::RELATIONS)
            ->where('user_id', $request->user()->user_id)
            ->orderByDesc('created_at'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'route_id' => 'required|integer|exists:tour_routes,route_id',
            'shift_id' => 'required|integer|exists:guide_shifts,shift_id',
            'wni_count' => 'sometimes|integer|min:0|max:50',
            'wna_count' => 'sometimes|integer|min:0|max:50',
            'student_count' => 'sometimes|integer|min:0|max:50',
            'drone_count' => 'sometimes|integer|min:0|max:5',
            'porter_count' => 'sometimes|integer|min:0|max:20',
            'contact_name' => 'required|string|max:100',
            'contact_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{8,30}$/'],
            'emergency_contact' => 'required|string|max:150',
            'notes' => 'nullable|string|max:500',
            'payment_method' => ['required', Rule::in(['bank_transfer', 'qris', 'ewallet'])],
            'participants' => 'required|array|min:1|max:50',
            'participants.*.full_name' => 'required|string|max:100',
            'participants.*.visitor_type' => ['required', Rule::in(['wni', 'wna', 'pelajar'])],
            'participants.*.age' => 'nullable|integer|min:0|max:120',
        ]);

        $this->bookings->expireStale();
        $booking = $this->bookings->create($request->user(), $data);

        return $this->success($this->present($booking->load(self::RELATIONS)), 'Pemesanan dibuat. Selesaikan pembayaran sebelum batas waktu.', 201);
    }

    public function show(Request $request, string $code)
    {
        $this->bookings->expireStale();

        return $this->success($this->present($this->own($request, $code)->load(self::RELATIONS)));
    }

    public function cancel(Request $request, string $code)
    {
        $booking = $this->bookings->cancel($this->own($request, $code));

        return $this->success($this->present($booking->load(self::RELATIONS)), 'Pemesanan dibatalkan.');
    }

    public function uploadProof(Request $request, string $code)
    {
        $request->validate(['file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120']);

        $this->bookings->submitProof($this->own($request, $code), $request->file('file'));
        $booking = $this->own($request, $code)->load(self::RELATIONS);

        return $this->success($this->present($booking), 'Bukti pembayaran terkirim. Petugas akan memverifikasi.');
    }

    public function ticket(Request $request, string $code)
    {
        $booking = $this->own($request, $code)->load(self::RELATIONS);
        if (! in_array($booking->status, ['paid', 'checked_in', 'completed'], true)) {
            return $this->error('Tiket tersedia setelah pembayaran diverifikasi.', 409);
        }

        return $this->success([
            'booking_code' => $booking->booking_code,
            'qr_payload' => $this->bookings->ticketPayload($booking),
            'status' => $booking->status,
            'visit_date' => $booking->visit_date->toDateString(),
            'route' => $booking->route?->name,
            'meeting_point' => $booking->route?->meeting_point,
            'start_time' => substr((string) $booking->shift?->start_time, 0, 5),
            'guide' => $booking->shift?->guide?->display_name,
            'participants' => $booking->participants->map->only(['full_name', 'visitor_type']),
            'checked_in_at' => $booking->checked_in_at?->toIso8601String(),
        ]);
    }

    public function review(Request $request, string $code)
    {
        $booking = $this->own($request, $code);
        if (! in_array($booking->status, ['checked_in', 'completed'], true)) {
            return $this->error('Ulasan bisa ditulis setelah kunjungan (tiket sudah di-check-in).', 409);
        }
        if ($booking->review()->exists()) {
            return $this->error('Pemesanan ini sudah diulas.', 409);
        }

        $data = $request->validate([
            'route_rating' => 'required|integer|between:1,5',
            'guide_rating' => 'nullable|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review = TourReview::create([
            'booking_id' => $booking->booking_id,
            'user_id' => $booking->user_id,
            'route_id' => $booking->route_id,
            'guide_id' => $booking->shift?->guide_id,
            'route_rating' => $data['route_rating'],
            'guide_rating' => $data['guide_rating'] ?? null,
            'comment' => isset($data['comment']) ? trim(strip_tags($data['comment'])) : null,
        ]);

        return $this->success($review, 'Terima kasih atas ulasannya.', 201);
    }

    public function payOnline(Request $request, string $code, MidtransService $midtrans)
    {
        if (! MidtransService::enabled()) {
            return $this->error('Pembayaran online belum diaktifkan pengelola.', 503);
        }
        $this->bookings->expireStale();
        $booking = $this->own($request, $code);
        if ($booking->status !== 'pending_payment' || ($booking->expires_at && $booking->expires_at->isPast())) {
            return $this->error('Pemesanan tidak sedang menunggu pembayaran.', 409);
        }

        try {
            $result = $midtrans->createTransaction($booking->load('user'));
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 502);
        }

        return $this->success([
            'redirect_url' => $result['redirect_url'],
            'reference' => $result['payment']->reference,
        ], 'Lanjutkan pembayaran di halaman Midtrans.');
    }

    public function simulatePayment(Request $request, string $code)
    {
        if (app()->isProduction() || ! config('services.tourism.payment_simulator')) {
            return $this->error('Simulator pembayaran tidak aktif.', 503);
        }

        $data = $request->validate([
            'channel' => ['required', Rule::in(['qris', 'va_bca', 'va_bni', 'va_bri', 'va_mandiri', 'gopay', 'ovo', 'dana', 'shopeepay'])],
            'outcome' => ['sometimes', Rule::in(['paid', 'failed'])],
        ]);

        $this->bookings->expireStale();
        $booking = $this->own($request, $code);
        if ($booking->status !== 'pending_payment' || ($booking->expires_at && $booking->expires_at->isPast())) {
            return $this->error('Pemesanan tidak sedang menunggu pembayaran.', 409);
        }

        $outcome = $data['outcome'] ?? 'paid';
        $channel = $data['channel'];

        $payment = $booking->payments()->create([
            'reference' => 'SIM-'.strtoupper(Str::random(10)),
            'method' => 'gateway',
            'amount' => $booking->total_amount,
            'external_reference' => $channel,
            'status' => 'pending',
        ]);

        if ($outcome === 'failed') {
            $payment->status = 'rejected';
            $payment->rejection_reason = "Simulasi pembayaran gagal melalui kanal {$channel}.";
            $payment->save();

            return $this->success([
                'booking' => $this->present($booking->fresh()->load(self::RELATIONS)),
                'simulation' => [
                    'channel' => $channel,
                    'outcome' => 'failed',
                    'transaction_id' => null,
                    'verified_automatically' => false,
                ],
            ], 'Simulasi pembayaran gagal. Anda dapat mencoba kanal lain.');
        }

        $txId = 'SIM-'.strtoupper($channel).'-'.strtoupper(Str::random(10));
        $booking = $this->bookings->markPaid($payment, null, $txId);

        return $this->success([
            'booking' => $this->present($booking->load(self::RELATIONS)),
            'simulation' => [
                'channel' => $channel,
                'outcome' => 'paid',
                'transaction_id' => $txId,
                'verified_automatically' => true,
            ],
        ], 'Pembayaran terverifikasi otomatis. E-tiket siap digunakan.');
    }

    private function own(Request $request, string $code): Booking
    {
        return Booking::where('booking_code', $code)->where('user_id', $request->user()->user_id)->firstOrFail();
    }

    private function present(Booking $booking): array
    {
        $data = $booking->toArray();
        $data['payment_window_seconds_left'] = $booking->status === 'pending_payment' && $booking->expires_at
            ? max(0, now()->diffInSeconds($booking->expires_at, false))
            : null;

        return $data;
    }
}
