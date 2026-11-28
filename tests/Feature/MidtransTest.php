<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\GuideShift;
use App\Models\Payment;
use App\Services\Tourism\TourismClock;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MidtransTest extends TestCase
{
    private const KEY = 'SB-Mid-server-UJI-123';

    private array $status = [];

    private function fakeMidtrans(): void
    {
        Http::fake([
            'app.sandbox.midtrans.com/*' => Http::response(['token' => 'snap-token', 'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/snap-token'], 201),
            'api.sandbox.midtrans.com/*' => fn () => Http::response($this->status),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAll();
        config(['services.midtrans.server_key' => self::KEY, 'services.midtrans.production' => false]);
    }

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app['auth']->forgetGuards();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    private function booking(array $h): string
    {
        $shift = GuideShift::whereDate('shift_date', '>', TourismClock::today())->orderBy('shift_date')->firstOrFail();

        return $this->postJson('/api/visitor/bookings', [
            'route_id' => $shift->route_id, 'shift_id' => $shift->shift_id, 'wni_count' => 1, 'wna_count' => 1,
            'contact_name' => 'Budi', 'contact_phone' => '081234567890', 'emergency_contact' => 'Ani 0812',
            'payment_method' => 'bank_transfer',
            'participants' => [['full_name' => 'Budi', 'visitor_type' => 'wni'], ['full_name' => 'John', 'visitor_type' => 'wna']],
        ], $h)->assertCreated()->json('data.booking_code');
    }

    private function notify(Payment $payment, array $override = [])
    {
        $body = ['order_id' => $payment->reference, 'status_code' => '200', 'gross_amount' => $payment->amount.'.00'];
        $body['signature_key'] = hash('sha512', $body['order_id'].$body['status_code'].$body['gross_amount'].self::KEY);

        return $this->postJson('/api/payments/midtrans/notification', array_merge($body, $override));
    }

    public function test_disabled_without_server_key(): void
    {
        config(['services.midtrans.server_key' => '']);
        $h = $this->loginAs('pengunjung');
        $code = $this->booking($h);

        $this->postJson("/api/visitor/bookings/{$code}/pay-online", [], $h)->assertStatus(503);
        $this->postJson('/api/payments/midtrans/notification', [])->assertStatus(503);
        $this->getJson('/api/tourism/payment-info')->assertJsonPath('data.midtrans_enabled', false);
    }

    public function test_full_online_payment_flow(): void
    {
        $this->fakeMidtrans();
        $h = $this->loginAs('pengunjung');
        $code = $this->booking($h);
        $booking = Booking::where('booking_code', $code)->first();

        $this->postJson("/api/visitor/bookings/{$code}/pay-online", [], $h)->assertOk()
            ->assertJsonPath('data.redirect_url', 'https://app.sandbox.midtrans.com/snap/v4/redirection/snap-token');

        Http::assertSent(function (ClientRequest $request) use ($booking) {
            $items = collect($request['item_details'])->sum(fn ($item) => $item['price'] * $item['quantity']);

            return $request->hasHeader('Authorization', 'Basic '.base64_encode(self::KEY.':'))
                && $request['transaction_details']['gross_amount'] === $booking->total_amount
                && $items === $booking->total_amount;
        });

        $payment = Payment::where('booking_id', $booking->booking_id)->where('method', 'gateway')->first();

        $this->notify($payment, ['signature_key' => str_repeat('0', 128)])->assertUnauthorized();
        $this->assertSame('pending_payment', $booking->fresh()->status);

        $this->status = ['transaction_status' => 'pending', 'gross_amount' => $payment->amount.'.00', 'transaction_id' => 'trx-1'];
        $this->notify($payment, ['transaction_status' => 'settlement'])->assertOk()->assertJsonPath('data.status', 'pending');
        $this->assertSame('pending_payment', $booking->fresh()->status);

        $this->status = ['transaction_status' => 'settlement', 'gross_amount' => $payment->amount.'.00', 'transaction_id' => 'trx-1'];
        $this->notify($payment)->assertOk()->assertJsonPath('data.status', 'paid');
        $this->notify($payment)->assertOk();

        $booking->refresh();
        $this->assertSame('paid', $booking->status);
        $this->assertNotNull($booking->ticket_token_hash);
        $this->assertSame('expired', Payment::where('booking_id', $booking->booking_id)->where('method', 'bank_transfer')->value('status'));
        $this->getJson("/api/visitor/bookings/{$code}/ticket", $h)->assertOk();
    }

    public function test_amount_mismatch_and_denied_payments_do_not_issue_tickets(): void
    {
        $this->fakeMidtrans();
        $h = $this->loginAs('pengunjung');
        $code = $this->booking($h);
        $this->postJson("/api/visitor/bookings/{$code}/pay-online", [], $h)->assertOk();
        $payment = Payment::where('method', 'gateway')->latest('payment_id')->first();

        $this->status = ['transaction_status' => 'settlement', 'gross_amount' => '1000.00', 'transaction_id' => 'x'];
        $this->notify($payment)->assertOk()->assertJsonPath('data.status', 'amount_mismatch');
        $this->assertSame('pending_payment', Booking::where('booking_code', $code)->value('status'));

        $this->status = ['transaction_status' => 'deny', 'gross_amount' => $payment->amount.'.00'];
        $this->notify($payment)->assertOk()->assertJsonPath('data.status', 'expired');
    }

    public function test_cannot_pay_online_for_someone_elses_booking(): void
    {
        $code = $this->booking($this->loginAs('pengunjung'));
        $this->postJson("/api/visitor/bookings/{$code}/pay-online", [], $this->loginAs('admin'))->assertNotFound();
    }
}
