<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrationGuard
{
    private const TTL_SECONDS = 600;

    public static function enabled(): bool
    {
        return (bool) config('services.registration.captcha', true);
    }

    public static function issue(): array
    {
        $a = random_int(2, 15);
        $b = random_int(2, 9);
        $op = random_int(0, 1) === 1 ? '+' : '-';
        if ($op === '-' && $b > $a) {
            [$a, $b] = [$b, $a];
        }

        $payload = rtrim(strtr(base64_encode(json_encode([
            'a' => $a, 'b' => $b, 'op' => $op, 'iat' => time(), 'nonce' => Str::random(16),
        ])), '+/', '-_'), '=');

        return [
            'challenge_token' => $payload.'.'.self::sign($payload),
            'question' => "Berapa {$a} {$op} {$b}?",
            'expires_in' => self::TTL_SECONDS,
        ];
    }

    public static function verify(?string $token, mixed $answer): void
    {
        $fail = fn (string $message) => throw ValidationException::withMessages(['captcha_answer' => $message]);

        [$payload, $signature] = array_pad(explode('.', (string) $token, 2), 2, '');
        if ($payload === '' || ! hash_equals(self::sign($payload), $signature)) {
            $fail('Soal keamanan tidak valid. Muat ulang soal.');
        }

        $data = json_decode((string) base64_decode(strtr($payload, '-_', '+/')), true);
        if (! is_array($data) || ! isset($data['a'], $data['b'], $data['op'], $data['iat'], $data['nonce'])) {
            $fail('Soal keamanan tidak valid. Muat ulang soal.');
        }

        $age = time() - (int) $data['iat'];
        if ($age > self::TTL_SECONDS) {
            $fail('Soal keamanan kedaluwarsa. Muat ulang soal.');
        }
        if ($age < (int) config('services.registration.captcha_min_seconds', 2)) {
            $fail('Pengisian terlalu cepat. Coba lagi.');
        }

        $expected = $data['op'] === '+' ? $data['a'] + $data['b'] : $data['a'] - $data['b'];
        if (! is_numeric($answer) || (int) $answer !== $expected) {
            $fail('Jawaban soal keamanan salah.');
        }

        if (! Cache::add('registration_captcha:'.$data['nonce'], 1, self::TTL_SECONDS)) {
            $fail('Soal keamanan sudah dipakai. Muat ulang soal.');
        }
    }

    private static function sign(string $payload): string
    {
        return hash_hmac('sha256', 'captcha|'.$payload, (string) config('app.key'));
    }
}
