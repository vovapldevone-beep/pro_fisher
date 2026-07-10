<?php

use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', SitemapController::class);

// SPA shell for every other route, with per-route SEO rendered in PHP.
// The regex excludes API, framework and the sitemap paths.
Route::get('/{any}', SpaController::class)
    ->where('any', '^(?!api|sanctum|storage|up|sitemap\.xml).*$');

Route::get('/', SpaController::class);
