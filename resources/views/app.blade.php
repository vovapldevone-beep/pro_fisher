<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="apple-touch-icon" href="/favicon.png">

    {{-- SEO: rendered in PHP so non-JS crawlers get real per-route tags. --}}
    <title>{{ $meta['title'] }}</title>
    <meta name="description" content="{{ $meta['description'] }}">
    <link rel="canonical" href="{{ $meta['canonical'] }}">
    <meta name="robots" content="{{ $meta['index'] ? 'index, follow' : 'noindex, follow' }}">

    <meta property="og:site_name" content="Pro Fisher">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $meta['title'] }}">
    <meta property="og:description" content="{{ $meta['description'] }}">
    <meta property="og:url" content="{{ $meta['canonical'] }}">
    <meta property="og:image" content="{{ $meta['image'] }}">
    <meta property="og:locale" content="uk_UA">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $meta['title'] }}">
    <meta name="twitter:description" content="{{ $meta['description'] }}">
    <meta name="twitter:image" content="{{ $meta['image'] }}">

    @isset($jsonLd)
        @if ($jsonLd)
            <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
        @endif
    @endisset

    @if ($gaId = config('services.google_analytics.id'))
        {{-- Google tag (gtag.js) --}}
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', '{{ $gaId }}');
        </script>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div id="app"></div>

    {{--
        Content for crawlers that do not run JS (Bing, Yandex). <noscript> is the
        textbook container for "what to read when scripts don't run" — no search
        engine treats it as hidden-text cloaking, and it never flashes for real
        users. Only lake pages, since they are the actual search targets.
    --}}
    @isset($lake)
        @if ($lake)
            <noscript>
                <h1>Озеро {{ $lake->name }}</h1>
                @if ($lake->region)<p>{{ $lake->region }}, Польща</p>@endif
                @if ($lake->description)<p>{{ $lake->description }}</p>@endif
                @if ($lake->fish_species)<p>Види риби: {{ $lake->fish_species }}</p>@endif
                @if ($lake->price)<p>Дозвіл від {{ $lake->price }} PLN / день</p>@endif
                @if ($lake->rating)<p>Рейтинг: {{ $lake->rating }} / 5 ({{ $lake->reviews->count() }} відгуків)</p>@endif

                @if ($lake->recentCatches->isNotEmpty())
                    <h2>Останні улови на озері {{ $lake->name }}</h2>
                    <ul>
                        @foreach ($lake->recentCatches as $catch)
                            <li>
                                {{ $catch->fish_name }}@if ($catch->weight), {{ $catch->weight }} кг@endif
                                @if ($catch->user) — зловив {{ $catch->user->name }}@endif
                                @if ($catch->caught_at) ({{ $catch->caught_at->format('d.m.Y') }})@endif
                                @if ($catch->notes) — {{ $catch->notes }}@endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($lake->reviews->isNotEmpty())
                    <h2>Відгуки рибалок</h2>
                    @foreach ($lake->reviews->take(10) as $review)
                        <article>
                            <strong>{{ $review->author_name }}</strong> — {{ $review->rating }}/5
                            <p>{{ $review->comment }}</p>
                        </article>
                    @endforeach
                @endif

                <p><a href="{{ rtrim(config('app.url'), '/') }}/map">Усі озера на карті</a></p>
            </noscript>
        @endif
    @endisset
</body>
</html>
