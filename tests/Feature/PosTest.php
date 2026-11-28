<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\GuideShift;
use App\Models\TourRoute;
use App\Services\Tourism\TourismClock;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAll();
    }

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app['auth']->forgetGuards();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    private function tomorrowShift(): GuideShift
    {
        return GuideShift::whereDate('shift_date', '>', TourismClock::today())->where('status', 'scheduled')
            ->whereHas('route', fn ($q) => $q->where('slug', 'bukit-lawang-orangutan-trek'))
            ->orderBy('shift_date')->firstOrFail();
    }

    private function sale(GuideShift $shift, array $overrides = []): array
    {
        return array_merge([
            'route_id' => $shift->route_id,
            'shift_id' => $shift->shift_id,
            'wni_count' => 2,
            'wna_count' => 1,
            'contact_name' => 'Andi Walk-in',
            'payment_method' => 'cash',
            'received_amount' => 2_000_000,
        ], $overrides);
    }

    private function openSession(array $h, int $cash = 200_000): void
    {
        $this->postJson('/api/pos/session/open', ['counter_name' => 'Loket Bukit Lawang', 'opening_cash' => $cash], $h)->assertCreated();
    }

    public function test_cashier_must_open_session_and_only_one_at_a_time(): void
    {
        $h = $this->loginAs('loket');
        $this->postJson('/api/pos/sales', $this->sale($this->tomorrowShift()), $h)->assertStatus(409);

        $this->openSession($h);
        $this->postJson('/api/pos/session/open', ['counter_name' => 'Loket 2', 'opening_cash' => 0], $h)->assertStatus(409);
        $this->getJson('/api/pos/session', $h)->assertOk()->assertJsonPath('data.is_open', true);
    }

    public function test_walk_in_sale_is_priced_by_server_paid_and_issues_ticket(): void
    {
        $h = $this->loginAs('loket');
        $this->openSession($h);
        $shift = $this->tomorrowShift();
        $route = TourRoute::find($shift->route_id);
        $isHoliday = Carbon::parse($shift->shift_date)->isSunday();
        $expected = 2 * ($isHoliday ? 15_000 : 10_000) + 150_000 + 3 * $route->guide_fee_per_person;

        $res = $this->postJson('/api/pos/sales', $this->sale($shift, ['total_amount' => 1, 'status' => 'pending_payment']), $h)
            ->assertCreated()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.channel', 'pos')
            ->assertJsonPath('data.total_amount', $expected)
            ->assertJsonPath('data.payments.0.change_amount', 2_000_000 - $expected)
            ->assertJsonPath('data.participants.0.full_name', 'Andi Walk-in')
            ->assertJsonPath('data.participants.2.visitor_type', 'wna');

        $this->assertNull(Booking::where('booking_code', $res->json('data.booking_code'))->value('user_id'));

        $this->travelTo(TourismClock::at($shift->shift_date->toDateString(), '07:00'));
        $this->postJson('/api/tourism/check-in', ['ticket' => $res->json('data.qr_payload')], $this->loginAs('lapangan'))->assertOk();
    }

    public function test_cash_must_cover_total(): void
    {
        $h = $this->loginAs('loket');
        $this->openSession($h);

        $this->postJson('/api/pos/sales', $this->sale($this->tomorrowShift(), ['received_amount' => 5000]), $h)
            ->assertUnprocessable()->assertJsonValidationErrors('received_amount');
    }

    public function test_pos_and_online_share_shift_capacity(): void
    {
        $h = $this->loginAs('loket');
        $this->openSession($h);
        $shift = $this->tomorrowShift();
        $shift->update(['capacity' => 3]);

        $this->postJson('/api/pos/sales', $this->sale($shift), $h)->assertCreated();
        $this->postJson('/api/pos/sales', $this->sale($shift, ['wni_count' => 1, 'wna_count' => 0]), $h)->assertStatus(409);
    }

    public function test_started_shift_cannot_be_sold_after_grace_period(): void
    {
        $h = $this->loginAs('loket');
        $this->openSession($h);
        $shift = $this->tomorrowShift();
        $this->travelTo(TourismClock::at($shift->shift_date->toDateString(), (string) $shift->start_time)->addMinutes(31));
        $h = $this->loginAs('loket');

        $this->postJson('/api/pos/sales', $this->sale($shift), $h)->assertUnprocessable()->assertJsonValidationErrors('shift_id');
    }

    public function test_void_and_close_session_compute_cash_difference(): void
    {
        $h = $this->loginAs('loket');
        $this->openSession($h, 100_000);
        $shift = $this->tomorrowShift();

        $a = $this->postJson('/api/pos/sales', $this->sale($shift), $h)->json('data');
        $b = $this->postJson('/api/pos/sales', $this->sale($shift, ['wni_count' => 1, 'wna_count' => 0, 'payment_method' => 'qris', 'received_amount' => null]), $h)->json('data');

        $this->postJson("/api/pos/sales/{$b['booking_code']}/void", ['reason' => 'x'], $h)->assertUnprocessable();
        $this->postJson("/api/pos/sales/{$b['booking_code']}/void", ['reason' => 'pelanggan batal'], $h)
            ->assertOk()->assertJsonPath('data.status', 'cancelled')->assertJsonPath('data.qr_payload', null);

        $expected = 100_000 + $a['total_amount'];
        $this->getJson('/api/pos/sessions', $h)->assertOk()->assertJsonPath('data.0.summary.transactions', 1)->assertJsonPath('data.0.is_open', true);
        $this->getJson('/api/pos/session', $h)->assertJsonPath('data.expected_cash_now', $expected)
            ->assertJsonPath('data.summary.transactions', 1)->assertJsonPath('data.summary.voided', 1);

        $this->postJson('/api/pos/session/close', ['counted_cash' => $expected - 5000, 'note' => 'kurang'], $h)
            ->assertOk()->assertJsonPath('data.cash_difference', -5000)->assertJsonPath('data.is_open', false);

        $this->postJson("/api/pos/sales/{$a['booking_code']}/void", ['reason' => 'pelanggan batal'], $h)->assertStatus(409);
        $this->postJson('/api/pos/sales', $this->sale($shift), $h)->assertStatus(409);
    }

    public function test_cashiers_are_isolated_and_report_is_admin_only(): void
    {
        $loket = $this->loginAs('loket');
        $this->openSession($loket);
        $code = $this->postJson('/api/pos/sales', $this->sale($this->tomorrowShift()), $loket)->json('data.booking_code');

        $admin = $this->loginAs('admin');
        $this->postJson("/api/pos/sales/{$code}/void", ['reason' => 'coba void'], $admin)->assertStatus(409);
        $this->getJson("/api/pos/sales/{$code}", $admin)->assertOk();

        $this->getJson('/api/pos/report', $loket)->assertForbidden();
        $report = $this->getJson('/api/pos/report', $admin)->assertOk();
        $this->assertSame(1, $report->json('data.summary.transactions'));
        $this->assertSame($report->json('data.summary.gross'), $report->json('data.summary.pnbp') + $report->json('data.summary.guide_fee'));

        foreach (['pengunjung', 'peneliti', 'lapangan'] as $user) {
            $this->getJson('/api/pos/session', $this->loginAs($user))->assertForbidden();
        }
        $this->getJson('/api/visitor/bookings', $this->loginAs('pengunjung'))->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_sale_requires_valid_payment_method(): void
    {
        $h = $this->loginAs('loket');
        $this->openSession($h);
        $this->postJson('/api/pos/sales', $this->sale($this->tomorrowShift(), ['payment_method' => 'bank_transfer']), $h)
            ->assertUnprocessable()->assertJsonValidationErrors('payment_method');
    }
}
