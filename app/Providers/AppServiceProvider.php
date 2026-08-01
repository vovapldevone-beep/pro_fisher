<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // shared hosting namecheap
        Schema::defaultStringLength(191);

        $this->configureRateLimiting();
    }

    /**
     * Named limiters for the api group and the write endpoints.
     *
     * Counted per user where there is one, per IP otherwise: a logged-in
     * spammer switching networks stays on the same bucket, and one office
     * or mobile NAT does not share a quota between its visitors.
     */
    private function configureRateLimiting(): void
    {
        // Baseline for every /api route. Wide enough that normal browsing —
        // a page of posts plus its comments and avatars — never touches it.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(90)->by($this->actor($request)));

        // Two limits: the burst one stops a script hammering the endpoint,
        // the hourly one still allows unloading a whole trip's photos.
        RateLimiter::for('publications', fn (Request $request) => [
            Limit::perMinute(3)->by($this->actor($request))->response($this->tooFast()),
            Limit::perHour(20)->by($this->actor($request))->response($this->tooFast()),
        ]);

        RateLimiter::for('comments', fn (Request $request) => [
            Limit::perMinute(5)->by($this->actor($request))->response($this->tooFast()),
            Limit::perMinutes(10, 20)->by($this->actor($request))->response($this->tooFast()),
        ]);

        // Likes are a toggle, so a curious user can produce a lot of them
        // legitimately; the point here is only to stop automated churn.
        RateLimiter::for('likes', fn (Request $request) => Limit::perMinute(30)->by($this->actor($request)));

        // Keyed by email as well as IP so one attacker cannot lock out a
        // whole NAT, and a password spray does not get a fresh budget per
        // account it tries.
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)
                ->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip())
                ->response($this->tooFast('Забагато спроб входу. Зачекайте хвилину.')),
            Limit::perMinute(20)
                ->by($request->ip())
                ->response($this->tooFast('Забагато спроб входу. Зачекайте хвилину.')),
        ]);

        RateLimiter::for('register', fn (Request $request) => Limit::perHour(3)
            ->by($request->ip())
            ->response($this->tooFast('Забагато реєстрацій з цієї адреси. Спробуйте пізніше.')));
    }

    private function actor(Request $request): string
    {
        return (string) ($request->user()?->id ?: $request->ip());
    }

    /** Default 429 body is "Too Many Attempts." in English — the SPA shows it verbatim. */
    private function tooFast(string $message = 'Забагато запитів. Спробуйте трохи пізніше.'): callable
    {
        return fn (Request $request, array $headers) => response()->json([
            'message' => $message,
        ], 429, $headers);
    }
}
