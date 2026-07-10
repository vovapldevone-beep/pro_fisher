<?php

namespace App\Http\Controllers;

use App\Models\Lake;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Serves the Vue SPA shell, but with per-route SEO rendered in PHP.
 *
 * Googlebot runs the JS and picks up `@unhead/vue`, yet social crawlers
 * (Telegram, Facebook, WhatsApp, X) and JS-weak engines (Bing, Yandex —
 * relevant for a UA/PL audience) read only the raw HTML. Building the head and
 * a content block server-side needs nothing but PHP — no Node, no SSR — and it
 * runs live on every request, so freshly created lakes are covered instantly.
 */
class SpaController extends Controller
{
    private const SITE_NAME = 'Pro Fisher';

    public function __invoke(Request $request): View
    {
        return view('app', $this->seoFor($request));
    }

    private function seoFor(Request $request): array
    {
        $path = '/'.ltrim($request->path(), '/');
        $meta = $this->defaults($path);

        // /lakes/{slug} — the only public route with per-record content
        if (preg_match('#^/lakes/([^/]+)$#', $path, $m)) {
            return $this->lakeSeo($m[1], $meta);
        }

        $meta = match ($path) {
            '/' => array_merge($meta, [
                'title' => 'Pro Fisher — карта озер, улови та спільнота рибалок',
                'description' => 'Знайди найкращі місця для риболовлі: інтерактивна карта озер, актуальні улови, відгуки та дозволи в одному місці.',
                'heading' => 'Знайди найкращі місця для риболовлі',
                'index' => true,
            ]),
            '/map' => array_merge($meta, [
                'title' => 'Карта озер для риболовлі | Pro Fisher',
                'description' => 'Інтерактивна карта водойм: знайди озеро поруч, переглянь ціни на дозволи, види риби та відгуки рибалок.',
                'heading' => 'Карта озер для риболовлі',
                'index' => true,
            ]),
            default => $meta, // login, register, posts, fishers, cabinet, admin → noindex
        };

        return ['meta' => $meta, 'jsonLd' => null, 'lake' => null];
    }

    private function lakeSeo(string $slug, array $meta): array
    {
        $lake = Lake::with([
            'photos' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'),
            'reviews',
            // Fresh, keyword-rich user content indexed straight onto the lake page,
            // instead of thousands of thin standalone post URLs.
            'recentCatches' => fn ($q) => $q->where('type', 'catch')
                ->whereNotNull('fish_name')
                ->with('user:id,name')
                ->latest('caught_at')
                ->limit(10),
        ])->where('slug', $slug)->first();

        if (! $lake) {
            // Unknown lake: stay noindex, let the SPA show its own empty state
            return ['meta' => $meta, 'jsonLd' => null, 'lake' => null];
        }

        $description = collect([
            $lake->fish_species ? "Риба: {$lake->fish_species}." : null,
            $lake->rating ? "Рейтинг {$lake->rating}/5." : null,
            $lake->region ? "{$lake->region}, Польща." : null,
            'Відгуки, ціни на дозволи та останні улови на Pro Fisher.',
        ])->filter()->implode(' ');

        $primary = $lake->photos->first();
        $image = $primary
            ? (str_starts_with($primary->path, 'http') ? $primary->path : asset('storage/'.$primary->path))
            : asset('images/bg.png');

        $meta = array_merge($meta, [
            'title' => "Озеро {$lake->name} — риболовля, відгуки, дозволи | Pro Fisher",
            'description' => $description,
            'heading' => "Озеро {$lake->name}",
            'image' => $image,
            'index' => true,
        ]);

        return ['meta' => $meta, 'jsonLd' => $this->lakeJsonLd($lake, $meta, $image), 'lake' => $lake];
    }

    /**
     * Schema.org for the lake. `AggregateRating` drives star rich-results in
     * search; only emitted when there is real rating data to back it.
     */
    private function lakeJsonLd(Lake $lake, array $meta, string $image): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'TouristAttraction',
            'name' => "Озеро {$lake->name}",
            'url' => $meta['canonical'],
            'image' => $image,
        ];

        if ($lake->description) {
            $data['description'] = $lake->description;
        }

        if ($lake->latitude && $lake->longitude) {
            $data['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $lake->latitude,
                'longitude' => (float) $lake->longitude,
            ];
        }

        if ($lake->address || $lake->region) {
            $data['address'] = array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $lake->address ?: null,
                'addressRegion' => $lake->region ?: null,
                'addressCountry' => 'PL',
            ]);
        }

        $reviewCount = $lake->reviews->count();
        if ($lake->rating && $reviewCount > 0) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (float) $lake->rating,
                'reviewCount' => $reviewCount,
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }

        return $data;
    }

    private function defaults(string $path): array
    {
        return [
            'title' => self::SITE_NAME,
            'description' => 'Pro Fisher — карта озер, улови та спільнота рибалок.',
            'canonical' => rtrim(config('app.url'), '/').($path === '/' ? '' : $path),
            'image' => asset('images/bg.png'),
            'heading' => self::SITE_NAME,
            'index' => false,
        ];
    }
}
