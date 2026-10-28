<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function login(Request $request)
    {
        $request->validate([
            "username" => "required|string",
            "password" => "required|string",
        ]);

        $user = User::where("username", $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                "credentials" => ["Invalid credentials"],
            ])->status(401);
        }

        $user->tokens()->delete();
        $token = $user->createToken("api-token")->plainTextToken;

        return $this->success([
            "token" => $token,
            "user" => $user,
        ], "Login successful");
    }

    public function me(Request $request)
    {
        return $this->success($request->user(), "User retrieved");
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, "Logged out successfully");
    }
}
