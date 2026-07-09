<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        Auth::login($user);

        return response()->json([
            'user' => $user,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Nieprawidłowe dane logowania.',
            ], 401);
        }

        if (Auth::user()->is_blocked) {
            Auth::logout();
            return response()->json([
                'message' => 'Ваш акаунт заблоковано.',
            ], 403);
        }

        $request->session()->regenerate();

        return response()->json([
            'user' => Auth::user(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Wylogowano pomyślnie.',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'avatar' => 'nullable|image|max:4096',
        ]);

        $user = $request->user();
        $data = ['name' => $request->name];

        if ($request->hasFile('avatar')) {
            if ($user->avatar_url && ! str_starts_with($user->avatar_url, 'http')) {
                Storage::disk('public')->delete($user->avatar_url);
            }
            $data['avatar_url'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return response()->json(['user' => $user->fresh()]);
    }
}
