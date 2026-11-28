<?php

namespace App\Http\Controllers\Api;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Services\ApiSessionService;
use App\Services\RegistrationGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required_without:username|nullable|email',
            'username' => 'required_without:email|nullable|string',
            'password' => 'required|string',
        ]);

        $login = $request->filled('email') ? Str::lower($request->email) : $request->username;
        $throttleKey = 'login:'.$login.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return $this->error("Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.", 429);
        }

        $user = User::with('role')
            ->where($request->filled('email') ? 'email' : 'username', $login)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages(['email' => ['Email atau password salah.']]);
        }

        RateLimiter::clear($throttleKey);

        if ($user->status !== 'aktif') {
            return $this->error('Akun Anda tidak aktif. Hubungi administrator.', 403);
        }

        if (! $user->role) {
            return $this->error('Akun belum memiliki role. Hubungi administrator.', 403);
        }

        $user->tokens()->where('name', 'api-token')->delete();
        $newToken = $user->createToken('api-token');
        ApiSessionService::touch($newToken->accessToken);
        $token = $newToken->plainTextToken;

        ActivityLog::record($user->user_id, 'LOGIN', ['ip' => $request->ip()]);

        return $this->success([
            'user' => self::presentUser($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login berhasil');
    }

    public function registerChallenge()
    {
        return $this->success(RegistrationGuard::enabled() ? RegistrationGuard::issue() : null);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:100',
            'username' => 'required|string|max:50|alpha_dash|unique:users,username',
            'email' => 'required|email|max:150|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            'website' => 'prohibited',
            'captcha_token' => RegistrationGuard::enabled() ? 'required|string|max:500' : 'nullable',
            'captcha_answer' => RegistrationGuard::enabled() ? 'required' : 'nullable',
        ]);
        if (RegistrationGuard::enabled()) {
            RegistrationGuard::verify($data['captcha_token'], $data['captcha_answer']);
        }

        $roleId = Role::where('name_role', 'pengunjung')->value('role_id');
        if (! $roleId) {
            return $this->error('Pendaftaran pengunjung belum tersedia.', 503);
        }

        $user = User::create([
            'full_name' => $data['full_name'],
            'username' => $data['username'],
            'email' => Str::lower($data['email']),
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role_id' => $roleId,
            'status' => 'aktif',
        ])->load('role');

        $newToken = $user->createToken('api-token');
        ApiSessionService::touch($newToken->accessToken);
        ActivityLog::record($user->user_id, 'REGISTER', ['ip' => $request->ip()]);

        return $this->success([
            'user' => self::presentUser($user),
            'token' => $newToken->plainTextToken,
            'token_type' => 'Bearer',
        ], 'Pendaftaran berhasil', 201);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        ApiSessionService::forget($token?->id);
        $token?->delete();

        return $this->success(null, 'Logout berhasil');
    }

    public function refresh(Request $request)
    {
        $user = $request->user()->load('role');
        $old = $request->user()->currentAccessToken();
        ApiSessionService::forget($old?->id);
        $old?->delete();

        $newToken = $user->createToken('api-token');
        ApiSessionService::touch($newToken->accessToken);
        $token = $newToken->plainTextToken;

        return $this->success([
            'user' => self::presentUser($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Token berhasil diperbarui');
    }

    public function me(Request $request)
    {
        return $this->success(self::presentUser($request->user()->load('role.permissions')));
    }

    public static function presentUser(User $user): array
    {
        $role = $user->role;

        return [
            'user_id' => $user->user_id,
            'role_id' => $user->role_id,
            'username' => $user->username,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'address' => $user->address,
            'phone' => $user->phone,
            'status' => $user->status,
            'name_role' => $role?->name_role,
            'role' => $role ? [
                'role_id' => $role->role_id,
                'name_role' => $role->name_role,
                'role_name' => $role->name_role,
                'description' => $role->description,
            ] : null,
            'permissions' => $role ? $role->permissions->pluck('name_permission')->values() : [],
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}
