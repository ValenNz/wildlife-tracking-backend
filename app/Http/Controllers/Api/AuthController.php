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
        // 1. Ubah validasi dari 'username' menjadi 'email'
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'], 
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->failed('Validasi gagal.', 422, $validator->errors()->toArray());
        }

        $credentials = $validator->validated();

        // 2. Ubah query pencarian dari 'username' menjadi 'email'
        $user = User::query()
            ->where('email', $credentials['email'])
            ->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            return $this->failed('Email atau password salah.', 401, [
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

    private function presentUser(User $user): array
    {
        return [
            'user_id' => $user->user_id,
            'username' => $user->name, 
            'full_name' => $user->name, 
            'email' => $user->email,
            'role' => $user->role,
        ];
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
