<?php

namespace App\Services\Tourism;

use App\Models\Booking;
use App\Models\GuideShift;
use App\Models\Payment;
use App\Models\PosSession;
use App\Models\TourRoute;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PosService
{
    public const METHODS = ['cash', 'qris', 'edc'];

    public const LATE_SALE_MINUTES = 30;

    public function __construct(private BookingService $bookings, private PricingService $pricing) {}

    public function openSession(User $cashier, string $counter, int $openingCash): PosSession
    {
        return DB::transaction(function () use ($cashier, $counter, $openingCash) {
            User::whereKey($cashier->user_id)->lockForUpdate()->first();
            if ($this->currentSession($cashier)) {
                throw new ConflictHttpException('Masih ada sesi kas yang terbuka. Tutup sesi tersebut terlebih dahulu.');
            }

            return PosSession::create([
                'cashier_id' => $cashier->user_id,
                'counter_name' => $counter,
                'opened_at' => now(),
                'opening_cash' => $openingCash,
            ]);
        });
    }

    public function currentSession(User $cashier): ?PosSession
    {
        return PosSession::where('cashier_id', $cashier->user_id)->whereNull('closed_at')->latest('session_id')->first();
    }

    public function sell(User $cashier, array $data): Booking
    {
        return DB::transaction(function () use ($cashier, $data) {
            $session = PosSession::where('cashier_id', $cashier->user_id)->whereNull('closed_at')->lockForUpdate()->first();
            if (! $session) {
                throw new ConflictHttpException('Buka sesi kas terlebih dahulu sebelum menjual tiket.');
            }

            $shift = GuideShift::whereKey($data['shift_id'])->lockForUpdate()->firstOrFail();
            $route = TourRoute::whereKey($data['route_id'])->where('is_active', true)->firstOrFail();
            if ($shift->route_id !== $route->route_id || $shift->status !== 'scheduled') {
                throw ValidationException::withMessages(['shift_id' => 'Jadwal tidak tersedia untuk rute ini.']);
            }

            $date = $shift->shift_date->toDateString();
            if ($date < TourismClock::today()) {
                throw ValidationException::withMessages(['shift_id' => 'Jadwal sudah lewat.']);
            }
            $deadline = TourismClock::at($date, (string) $shift->start_time)->addMinutes(self::LATE_SALE_MINUTES);
            if (now()->gt($deadline)) {
                throw ValidationException::withMessages(['shift_id' => 'Shift sudah berjalan lebih dari '.self::LATE_SALE_MINUTES.' menit. Pilih shift lain.']);
            }

            $wni = (int) ($data['wni_count'] ?? 0);
            $wna = (int) ($data['wna_count'] ?? 0);
            $students = (int) ($data['student_count'] ?? 0);
            $drones = (int) ($data['drone_count'] ?? 0);
            $count = $wni + $wna + $students;

            $participants = $this->participants($data, $wni, $wna, $students);
            $this->bookings->assertParticipants($route, $participants, $wni, $wna, $students);

            $remaining = $this->bookings->remainingCapacity($shift);
            if ($count > $remaining) {
                throw new ConflictHttpException("Kuota shift ini tinggal {$remaining} orang.");
            }

            $quote = $this->pricing->quote($route, $date, $wni, $wna, $students, $drones);
            $total = (int) $quote['total'];

            $method = $data['payment_method'];
            $received = null;
            $change = null;
            if ($method === 'cash') {
                $received = (int) ($data['received_amount'] ?? 0);
                if ($received < $total) {
                    throw ValidationException::withMessages(['received_amount' => 'Uang diterima kurang dari total Rp'.number_format($total, 0, ',', '.').'.']);
                }
                $change = $received - $total;
            }

            $checkInNow = (bool) ($data['check_in_now'] ?? false) && $date === TourismClock::today();

            $booking = Booking::create([
                'booking_code' => $this->bookings->uniqueCode(Booking::class, 'booking_code', 'TNGL-'),
                'user_id' => null,
                'channel' => 'pos',
                'pos_session_id' => $session->session_id,
                'sold_by' => $cashier->user_id,
                'route_id' => $route->route_id,
                'shift_id' => $shift->shift_id,
                'visit_date' => $date,
                'day_type' => $quote['day_type'],
                'wni_count' => $wni,
                'wna_count' => $wna,
                'student_count' => $students,
                'drone_count' => $drones,
                'participant_count' => $count,
                'price_lines' => $quote['lines'],
                'total_amount' => $total,
                'status' => 'paid',
                'expires_at' => null,
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'] ?? '-',
                'emergency_contact' => $data['emergency_contact'] ?? '-',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($participants as $participant) {
                $booking->participants()->create($participant);
            }

            $payment = $booking->payments()->create([
                'reference' => $this->bookings->uniqueCode(Payment::class, 'reference', 'POS-', 12),
                'method' => $method,
                'amount' => $total,
                'received_amount' => $received,
                'change_amount' => $change,
                'external_reference' => $data['external_reference'] ?? null,
                'status' => 'paid',
            ]);
            $payment->forceFill(['paid_at' => now(), 'verified_by' => $cashier->user_id])->save();

            $booking->forceFill(['ticket_token_hash' => hash('sha256', $this->bookings->ticketToken($booking))]);
            if ($checkInNow) {
                $booking->forceFill(['status' => 'checked_in', 'checked_in_at' => now(), 'checked_in_by' => $cashier->user_id]);
            }
            $booking->save();

            return $booking;
        });
    }

    public function void(Booking $sale, User $cashier, string $reason): Booking
    {
        return DB::transaction(function () use ($sale, $cashier, $reason) {
            $sale = Booking::whereKey($sale->booking_id)->lockForUpdate()->firstOrFail();
            $session = $sale->pos_session_id ? PosSession::whereKey($sale->pos_session_id)->lockForUpdate()->first() : null;

            if ($sale->channel !== 'pos' || ! $session || $session->cashier_id !== $cashier->user_id) {
                throw new ConflictHttpException('Hanya kasir penjual yang bisa membatalkan transaksi ini.');
            }
            if (! $session->isOpen()) {
                throw new ConflictHttpException('Sesi kas sudah ditutup. Pembatalan harus lewat admin (refund).');
            }
            if (! in_array($sale->status, ['paid', 'checked_in'], true)) {
                throw new ConflictHttpException('Transaksi ini sudah dibatalkan.');
            }

            $sale->forceFill([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'ticket_token_hash' => null,
            ])->save();
            $sale->payments()->where('status', 'paid')->update(['status' => 'refunded', 'rejection_reason' => 'VOID: '.$reason]);

            return $sale;
        });
    }

    public function closeSession(PosSession $session, int $countedCash, ?string $note): PosSession
    {
        return DB::transaction(function () use ($session, $countedCash, $note) {
            $session = PosSession::whereKey($session->session_id)->lockForUpdate()->first();
            if (! $session->isOpen()) {
                throw new ConflictHttpException('Sesi ini sudah ditutup.');
            }

            $expected = $this->expectedCash($session);
            $session->forceFill([
                'closed_at' => now(),
                'expected_cash' => $expected,
                'counted_cash' => $countedCash,
                'cash_difference' => $countedCash - $expected,
                'closing_note' => $note,
            ])->save();

            return $session;
        });
    }

    public function expectedCash(PosSession $session): int
    {
        $cash = Payment::whereHas('booking', fn ($q) => $q->where('pos_session_id', $session->session_id))
            ->where('method', 'cash')->where('status', 'paid')->sum('amount');

        return (int) $session->opening_cash + (int) $cash;
    }

    public function summarize($bookings): array
    {
        $rows = $bookings->with('payments')->get();
        $valid = $rows->whereIn('status', ['paid', 'checked_in', 'completed']);

        $pnbp = 0;
        $guide = 0;
        foreach ($valid as $booking) {
            foreach ($booking->price_lines as $line) {
                if (($line['code'] ?? '') === 'guide_fee') {
                    $guide += (int) $line['subtotal'];
                } else {
                    $pnbp += (int) $line['subtotal'];
                }
            }
        }

        $byMethod = [];
        foreach ($valid as $booking) {
            foreach ($booking->payments->where('status', 'paid') as $payment) {
                $byMethod[$payment->method] = ($byMethod[$payment->method] ?? 0) + $payment->amount;
            }
        }

        $byChannel = [];
        foreach ($valid->groupBy('channel') as $channel => $items) {
            $byChannel[$channel] = ['transactions' => $items->count(), 'amount' => (int) $items->sum('total_amount')];
        }

        return [
            'transactions' => $valid->count(),
            'voided' => $rows->where('status', 'cancelled')->count(),
            'visitors' => [
                'wni' => (int) $valid->sum('wni_count'),
                'wna' => (int) $valid->sum('wna_count'),
                'pelajar' => (int) $valid->sum('student_count'),
                'drone' => (int) $valid->sum('drone_count'),
                'total' => (int) $valid->sum('participant_count'),
            ],
            'gross' => (int) $valid->sum('total_amount'),
            'pnbp' => $pnbp,
            'guide_fee' => $guide,
            'by_method' => $byMethod,
            'by_channel' => $byChannel,
        ];
    }

    private function participants(array $data, int $wni, int $wna, int $students): array
    {
        $given = collect($data['participants'] ?? [])->groupBy('visitor_type');
        $labels = ['wni' => 'WNI', 'wna' => 'WNA', 'pelajar' => 'Pelajar'];
        $result = [];
        $n = 0;

        foreach (['wni' => $wni, 'pelajar' => $students, 'wna' => $wna] as $type => $count) {
            $names = $given->get($type, collect())->values();
            for ($i = 0; $i < $count; $i++) {
                $n++;
                $person = $names->get($i);
                $name = trim((string) ($person['full_name'] ?? ''));
                if ($name === '' && $n === 1) {
                    $name = trim($data['contact_name']);
                }
                $result[] = [
                    'full_name' => $name !== '' ? $name : "Peserta {$n} ({$labels[$type]})",
                    'visitor_type' => $type,
                    'age' => isset($person['age']) ? (int) $person['age'] : null,
                ];
            }
        }

        if (count($result) === 0) {
            throw ValidationException::withMessages(['participants' => 'Minimal satu pengunjung.']);
        }

        return $result;
    }
}
