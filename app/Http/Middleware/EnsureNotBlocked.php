<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kicks a blocked user out of an already-open session.
 *
 * Both login paths check `is_blocked`, but only at the moment of logging in —
 * so blocking a spammer in the admin panel left their live session posting
 * until the cookie expired. The session guard reloads the user from the
 * database on every request, so the flag is seen the moment it is set.
 */
class EnsureNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_blocked) {
            return $next($request);
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // `blocked` lets the frontend tell this apart from an admin-only 403
        return response()->json([
            'message' => 'Ваш акаунт заблоковано.',
            'blocked' => true,
        ], 403);
    }
}
