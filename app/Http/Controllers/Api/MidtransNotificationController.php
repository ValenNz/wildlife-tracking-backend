<?php

namespace App\Http\Controllers\Api;

use App\Services\Tourism\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransNotificationController extends ApiController
{
    public function __invoke(Request $request, MidtransService $midtrans)
    {
        if (! MidtransService::enabled()) {
            return $this->error('Pembayaran online belum diaktifkan.', 503);
        }

        $notification = $request->validate([
            'order_id' => 'required|string|max:50',
            'status_code' => 'required|string|max:5',
            'gross_amount' => 'required|string|max:20',
            'signature_key' => 'required|string|size:128',
        ]);

        if (! $midtrans->validSignature($notification)) {
            Log::warning('Notifikasi Midtrans dengan tanda tangan tidak valid', ['ip' => $request->ip(), 'order_id' => $notification['order_id']]);

            return $this->error('Tanda tangan tidak valid.', 401);
        }

        try {
            $status = $midtrans->handleNotification($notification['order_id']);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 503);
        }

        return $this->success(['status' => $status]);
    }
}
