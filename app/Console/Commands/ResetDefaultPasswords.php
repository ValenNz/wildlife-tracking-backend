<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ApiSessionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetDefaultPasswords extends Command
{
    protected $signature = 'users:reset-default-passwords {--dry-run : Hanya tampilkan akun yang terdampak}';

    protected $description = 'Ganti password bawaan seeder dengan password acak dan cabut token akun tersebut';

    public function handle(): int
    {
        $rows = [];
        foreach (User::query()->get() as $user) {
            if (! Hash::check('password123', $user->password)) {
                continue;
            }
            $password = $this->option('dry-run') ? '(tidak diubah)' : Str::password(14, symbols: false);
            if (! $this->option('dry-run')) {
                $user->forceFill(['password' => $password])->save();
                foreach ($user->tokens as $token) {
                    ApiSessionService::forget($token->id);
                }
                $user->tokens()->delete();
            }
            $rows[] = [$user->username, $user->email, $password];
        }

        if ($rows === []) {
            $this->info('Tidak ada akun yang memakai password bawaan.');

            return self::SUCCESS;
        }

        $this->table(['Username', 'Email', 'Password baru'], $rows);
        if (! $this->option('dry-run')) {
            $this->warn('Simpan password ini di tempat aman dan bagikan langsung ke pemilik akun. Password tidak ditampilkan lagi.');
        }

        return self::SUCCESS;
    }
}
