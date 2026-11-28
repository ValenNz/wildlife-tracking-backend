<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SecurityCheck extends Command
{
    protected $signature = 'security:check';

    protected $description = 'Periksa konfigurasi keamanan (debug, password bawaan, kunci, proxy, pembayaran)';

    public function handle(): int
    {
        $production = ! app()->environment('local', 'testing');
        $defaultPasswords = User::query()->get()->filter(fn (User $u) => Hash::check('password123', $u->password))->count();
        $checks = [
            ['APP_KEY terisi', (string) config('app.key') !== '', 'WAJIB', 'php artisan key:generate'],
            ['APP_DEBUG mati di server', ! ($production && config('app.debug')), 'WAJIB', 'APP_DEBUG=false (stack trace bocor bila true)'],
            ['Tidak ada password bawaan', $defaultPasswords === 0, 'WAJIB', "{$defaultPasswords} akun masih password123 → php artisan users:reset-default-passwords"],
            ['CAPTCHA pendaftaran aktif', (bool) config('services.registration.captcha'), 'SARAN', 'REGISTRATION_CAPTCHA=true'],
            ['Kunci Groq terisi (fallback AI)', (string) config('services.groq.key') !== '', 'SARAN', 'GROQ_API_KEY (rotasi bila pernah tersebar)'],
            ['Rahasia webhook pembayaran ≥ 32 karakter', strlen((string) config('services.tourism.webhook_secret')) === 0 || strlen((string) config('services.tourism.webhook_secret')) >= 32, 'WAJIB', 'PAYMENT_WEBHOOK_SECRET panjang & acak (atau kosongkan untuk menonaktifkan webhook)'],
            ['Rekening tujuan wisata diisi', (string) config('services.tourism.bank_account_number') !== '', 'SARAN', 'TOURISM_BANK_* di .env'],
            ['Simulator pembayaran mati di produksi', ! ($production && config('services.tourism.payment_simulator')), 'SARAN', 'Set PAYMENT_SIMULATOR_ENABLED=false di produksi dengan uang asli'],
            ['Proxy tepercaya dikonfigurasi', config('app.trusted_proxies') !== [], 'SARAN', 'TRUSTED_PROXIES=127.0.0.1 bila FE/cloudflared di mesin yang sama (rate limit per IP asli)'],
        ];

        $failed = false;
        $rows = [];
        foreach ($checks as [$name, $ok, $level, $fix]) {
            $rows[] = [$ok ? 'OK' : ($level === 'WAJIB' ? 'GAGAL' : 'PERINGATAN'), $name, $ok ? '' : $fix];
            $failed = $failed || (! $ok && $level === 'WAJIB');
        }
        $this->table(['Status', 'Pemeriksaan', 'Perbaikan'], $rows);

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
