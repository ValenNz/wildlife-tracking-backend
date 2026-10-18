<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    private const TOKEN_NAME = 'auth_token';

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->failed('Validasi gagal.', 422, $validator->errors()->toArray());
        }

        $credentials = $validator->validated();

        $user = User::query()
            ->where('username', $credentials['username'])
            ->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            return $this->failed('Username atau password salah.', 401, [
                'credentials' => ['Kredensial tidak valid'],
            ]);
        }

        $user->tokens()->delete();

        $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;

        ActivityLog::query()->create([
            'user_id' => $user->user_id,
            'action' => 'login',
            'details' => 'Login berhasil melalui API.',
        ]);

        return $this->success('Login berhasil', [
            'token' => $token,
            'user' => $this->presentUser($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success('Data pengguna berhasil diambil.', [
            'user' => $this->presentUser($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success('Logout berhasil.');
    }

    private function presentUser(User $user): array
    {
        return [
            'user_id' => $user->user_id,
            'username' => $user->username,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'role' => $user->role,
        ];
    }

    private function success(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $status);
    }

    private function failed(
        string $message,
        int $status,
        ?array $errors = null,
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $status);
    }
}
