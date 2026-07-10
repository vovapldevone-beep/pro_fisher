<?php

namespace App\Http\Controllers;

use App\Models\Lake;
use Illuminate\Http\Response;

/**
 * Live sitemap built from the DB — a lake created via the admin panel appears
 * on the next request with no rebuild. Lists only the public, indexable routes
 * (home, map, lake pages); everything else is auth-gated.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $base = rtrim(config('app.url'), '/');

        $urls = [
            ['loc' => $base.'/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => $base.'/map', 'priority' => '0.9', 'changefreq' => 'daily'],
        ];

        Lake::select('slug', 'updated_at')->orderBy('id')->chunk(500, function ($lakes) use (&$urls, $base) {
            foreach ($lakes as $lake) {
                $urls[] = [
                    'loc' => $base.'/lakes/'.$lake->slug,
                    'lastmod' => $lake->updated_at?->toAtomString(),
                    'priority' => '0.8',
                    'changefreq' => 'weekly',
                ];
            }
        });

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
