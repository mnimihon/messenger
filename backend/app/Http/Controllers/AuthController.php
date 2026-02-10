<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'avatar_url' => $this->generateAvatar($request->name),
        ]);

        $token = $user->createToken('auth_token', ['*'], now()->addDays(30));

        return response()->json([
            'user' => $this->formatUser($user),
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Неверные учетные данные'],
            ]);
        }

        $user = Auth::user();

        $token = $user->createToken('auth_token', ['*'], now()->addDays(30));

        return response()->json([
            'user' => $this->formatUser($user),
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Успешный выход'
        ]);
    }

    private function generateAvatar(string $name): string
    {
        $nameForAvatar = urlencode($name);
        return "https://api.dicebear.com/7.x/avataaars/svg?seed={$nameForAvatar}";
    }

    private function formatUser($user)
    {
        if (!$user) return null;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->avatar_url,
            'created_at' => $user->created_at,
        ];
    }

    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->avatar_url,
            'created_at' => $user->created_at,
            'email_verified_at' => $user->email_verified_at,
        ]);
    }
}
