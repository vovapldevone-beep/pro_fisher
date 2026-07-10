<?php

use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

// Google OAuth — web routes (not /api) so the callback has a session for Auth::login
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

Route::get('/sitemap.xml', SitemapController::class);

// SPA shell for every other route, with per-route SEO rendered in PHP.
// The regex excludes API, framework and the sitemap paths.
Route::get('/{any}', SpaController::class)
    ->where('any', '^(?!api|sanctum|storage|up|sitemap\.xml).*$');

Route::get('/', SpaController::class);
