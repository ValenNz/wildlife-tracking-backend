<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\PosSession;
use App\Services\Tourism\BookingService;
use App\Services\Tourism\PosService;
use App\Services\Tourism\TourismClock;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PosController extends ApiController
{
    private const SALE_RELATIONS = ['route:route_id,name,meeting_point,start_time', 'shift.guide:guide_id,display_name',
        'participants', 'payments', 'seller:user_id,full_name', 'posSession:session_id,counter_name'];

    public function __construct(private PosService $pos, private BookingService $bookings) {}

    public function current(Request $request)
    {
        $session = $this->pos->currentSession($request->user());

        return $this->success($session ? $this->presentSession($session) : null);
    }

    public function open(Request $request)
    {
        $data = $request->validate([
            'counter_name' => 'required|string|max:100',
            'opening_cash' => 'required|integer|min:0|max:100000000',
        ]);
        $session = $this->pos->openSession($request->user(), trim(strip_tags($data['counter_name'])), (int) $data['opening_cash']);

        return $this->success($this->presentSession($session), 'Sesi kas dibuka.', 201);
    }

    public function close(Request $request)
    {
        $data = $request->validate([
            'counted_cash' => 'required|integer|min:0|max:1000000000',
            'note' => 'nullable|string|max:500',
        ]);
        $session = $this->pos->currentSession($request->user());
        abort_unless($session, 409, 'Tidak ada sesi kas yang terbuka.');

        $closed = $this->pos->closeSession($session, (int) $data['counted_cash'], isset($data['note']) ? strip_tags($data['note']) : null);

        return $this->success($this->presentSession($closed), $closed->cash_difference === 0
            ? 'Sesi ditutup. Kas sesuai.'
            : 'Sesi ditutup dengan selisih kas '.($closed->cash_difference < 0 ? 'kurang' : 'lebih').' Rp'.number_format(abs($closed->cash_difference), 0, ',', '.').'.');
    }

    public function sessions(Request $request)
    {
        $data = $request->validate(['date' => 'sometimes|date', 'cashier_id' => 'sometimes|integer']);
        $isAdmin = $this->isAdmin($request);

        $sessions = PosSession::with('cashier:user_id,full_name')
            ->when(! $isAdmin, fn ($q) => $q->where('cashier_id', $request->user()->user_id))
            ->when($isAdmin && isset($data['cashier_id']), fn ($q) => $q->where('cashier_id', $data['cashier_id']))
            ->when($data['date'] ?? null, fn ($q, $d) => $q->whereDate('opened_at', $d))
            ->orderByDesc('session_id')
            ->limit(200)
            ->get()
            ->map(fn (PosSession $session) => $this->presentSession($session));

        return $this->list($sessions);
    }

    public function showSession(Request $request, int $id)
    {
        $session = $this->ownSession($request, $id);

        return $this->success($this->presentSession($session, withSales: true));
    }

    public function sales(Request $request)
    {
        $session = $this->pos->currentSession($request->user());

        return $this->list($session
            ? Booking::with(self::SALE_RELATIONS)->where('pos_session_id', $session->session_id)->orderByDesc('booking_id')
            : []);
    }

    public function sell(Request $request)
    {
        $data = $request->validate([
            'route_id' => 'required|integer|exists:tour_routes,route_id',
            'shift_id' => 'required|integer|exists:guide_shifts,shift_id',
            'wni_count' => 'sometimes|integer|min:0|max:50',
            'wna_count' => 'sometimes|integer|min:0|max:50',
            'student_count' => 'sometimes|integer|min:0|max:50',
            'drone_count' => 'sometimes|integer|min:0|max:5',
            'contact_name' => 'required|string|max:100',
            'contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{8,30}$/'],
            'emergency_contact' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:500',
            'participants' => 'sometimes|array|max:50',
            'participants.*.full_name' => 'nullable|string|max:100',
            'participants.*.visitor_type' => ['required_with:participants', Rule::in(['wni', 'wna', 'pelajar'])],
            'participants.*.age' => 'nullable|integer|min:0|max:120',
            'payment_method' => ['required', Rule::in(PosService::METHODS)],
            'received_amount' => 'required_if:payment_method,cash|nullable|integer|min:0|max:1000000000',
            'external_reference' => 'nullable|string|max:100',
            'check_in_now' => 'sometimes|boolean',
        ]);

        $sale = $this->pos->sell($request->user(), $data);

        return $this->success($this->presentSale($sale->load(self::SALE_RELATIONS)), 'Transaksi berhasil. Cetak struk & tiket.', 201);
    }

    public function showSale(Request $request, string $code)
    {
        return $this->success($this->presentSale($this->ownSale($request, $code)->load(self::SALE_RELATIONS)));
    }

    public function void(Request $request, string $code)
    {
        $data = $request->validate(['reason' => 'required|string|min:5|max:255']);
        $sale = $this->pos->void($this->ownSale($request, $code), $request->user(), strip_tags($data['reason']));

        return $this->success($this->presentSale($sale->load(self::SALE_RELATIONS)), 'Transaksi dibatalkan (void).');
    }

    public function dailyReport(Request $request)
    {
        $date = $request->validate(['date' => 'sometimes|date'])['date'] ?? TourismClock::today();

        [$from, $to] = [TourismClock::at($date, '00:00')->utc(), TourismClock::at($date, '00:00')->addDay()->utc()];
        $query = Booking::whereHas('payments', fn ($q) => $q->whereBetween('paid_at', [$from, $to]));

        return $this->success([
            'date' => $date,
            'timezone' => TourismClock::timezone(),
            'summary' => $this->pos->summarize(clone $query),
            'sessions' => PosSession::with('cashier:user_id,full_name')->whereBetween('opened_at', [$from, $to])->get()
                ->map(fn (PosSession $s) => $this->presentSession($s)),
        ]);
    }

    private function presentSession(PosSession $session, bool $withSales = false): array
    {
        $session->loadMissing('cashier:user_id,full_name');
        $data = $session->toArray();
        $data['is_open'] = $session->isOpen();
        $data['expected_cash_now'] = $session->isOpen() ? $this->pos->expectedCash($session) : $session->expected_cash;
        $data['summary'] = $this->pos->summarize(Booking::where('pos_session_id', $session->session_id));
        if ($withSales) {
            $data['sales'] = Booking::with(self::SALE_RELATIONS)->where('pos_session_id', $session->session_id)
                ->orderBy('booking_id')->get();
        }

        return $data;
    }

    private function presentSale(Booking $sale): array
    {
        $data = $sale->toArray();
        $data['qr_payload'] = in_array($sale->status, ['paid', 'checked_in'], true) ? $this->bookings->ticketPayload($sale) : null;

        return $data;
    }

    private function ownSession(Request $request, int $id): PosSession
    {
        $session = PosSession::findOrFail($id);
        if (! $this->isAdmin($request) && $session->cashier_id !== $request->user()->user_id) {
            throw new NotFoundHttpException;
        }

        return $session;
    }

    private function ownSale(Request $request, string $code): Booking
    {
        $sale = Booking::where('booking_code', $code)->where('channel', 'pos')->firstOrFail();
        if (! $this->isAdmin($request) && $sale->sold_by !== $request->user()->user_id) {
            throw new NotFoundHttpException;
        }

        return $sale;
    }

    private function isAdmin(Request $request): bool
    {
        return $request->user()->hasPermission('manage_tourism');
    }
}
