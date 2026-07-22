<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

/**
 * Google OAuth on top of the existing session (Sanctum stateful) auth.
 *
 * These are full-page browser redirects, not XHR, so they live on web routes:
 * the `web` middleware group gives them the session that `Auth::login` needs to
 * persist. A Google redirect is not a stateful-frontend request, so it would not
 * get a session under the `api` group.
 */
class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
            // User denied consent, or the token exchange failed
            return redirect('/login?error=google');
        }

        $user = $this->resolveUser($googleUser);

        if ($user->is_blocked) {
            return redirect('/login?error=blocked');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        // Home redirects an authenticated user to Пости
        return redirect('/');
    }

    private function resolveUser(\Laravel\Socialite\Contracts\User $googleUser): User
    {
        // Returning Google user — matched by their stable Google id
        if ($user = User::where('google_id', $googleUser->getId())->first()) {
            return $user;
        }

        // Same email as an existing password account → link the two, so the user
        // keeps one profile regardless of how they signed up. Emails from Google
        // are verified, so this is safe against hijacking.
        if ($user = User::where('email', $googleUser->getEmail())->first()) {
            $user->google_id = $googleUser->getId();
            $user->save();

            return $user;
        }

        // Brand-new user
        return User::create([
            'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Рибалка',
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar_url' => $googleUser->getAvatar(), // http URL, stored as-is
            'email_verified_at' => now(),
            'password' => null,
        ]);
    }
}
