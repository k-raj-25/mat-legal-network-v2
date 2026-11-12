@props(['title' => null, 'description' => null, 'noindex' => false, 'canonical' => null, 'disclaimer' => true, 'ogType' => 'website', 'ogImage' => null])
@php
    $fullTitle = $title ? $title.' | MAT LEGAL NETWORK' : 'MAT LEGAL NETWORK – Find Verified Lawyers Near You';
    $description = $description ?? 'Search verified lawyers across India by city, practice area, language and experience. Connect through MAT LEGAL NETWORK – one trusted number, no spam.';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="theme-color" content="#0a7f78">
    <meta name="color-scheme" content="light">
    @if ($noindex)<meta name="robots" content="noindex, nofollow">@endif
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    <meta property="og:site_name" content="MAT LEGAL NETWORK">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:type" content="{{ $ogType }}">
    @if ($ogImage)<meta property="og:image" content="{{ $ogImage }}"><meta name="twitter:card" content="summary_large_image">@endif
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    <meta name="copyright" content="© {{ date('Y') }} {{ $site['legal_entity_name'] }}. All rights reserved.">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>
<body class="flex min-h-screen flex-col bg-white">
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2">Skip to content</a>

<header x-data="{ open: false, scrolled: false }" @scroll.window="scrolled = window.scrollY > 8" class="sticky top-0 z-40 transition-all" :class="scrolled ? 'bg-white/85 shadow-[0_1px_0_rgb(11_19_36/0.06)] backdrop-blur-xl' : 'bg-white/0'">
    <div class="container-page flex h-[72px] items-center justify-between gap-4">
        <a href="{{ route('home') }}" aria-label="MAT LEGAL NETWORK home"><x-logo/></a>

        <nav class="hidden items-center gap-1 rounded-full bg-slate-100/80 p-1 lg:flex" aria-label="Main">
            @foreach ([['lawyers.index', 'Find a Lawyer'], ['for-lawyers', 'For Lawyers'], ['blog.index', 'Blog'], ['about', 'About'], ['contact', 'Contact']] as [$r, $l])
                <a href="{{ route($r) }}" class="rounded-full px-4 py-2 text-sm font-bold transition {{ request()->routeIs($r === 'blog.index' ? 'blog.*' : $r.'*') ? 'bg-white text-ink shadow-pop' : 'text-slate-600 hover:text-ink' }}" @if (request()->routeIs($r)) aria-current="page" @endif>{{ $l }}</a>
            @endforeach
        </nav>

        <div class="hidden items-center gap-2 lg:flex">
            <a href="tel:{{ preg_replace('/[^\d+]/', '', $site['company_phone']) }}" class="mr-1 inline-flex items-center gap-2 text-sm font-bold text-ink hover:text-brand-700">
                <span class="tile size-8 bg-brand-50 text-brand-600"><x-icon name="phone" class="size-4"/></span>{{ $site['company_phone'] }}
            </a>
            @auth
                <a href="{{ auth()->user()->homeRoute() }}" class="btn-ink">Dashboard</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-ghost">Log out</button></form>
            @else
                <a href="{{ route('login') }}" class="btn-ghost">Log in</a>
                <a href="{{ route('register') }}" class="btn-accent">Join as a Lawyer</a>
            @endauth
        </div>

        <button type="button" class="tile size-11 bg-slate-100 text-ink lg:hidden" @click="open = !open" :aria-expanded="open" aria-controls="mobile-menu" aria-label="Toggle menu">
            <x-icon name="menu" class="size-6" x-show="!open"/>
            <x-icon name="x" class="size-6" x-show="open" x-cloak/>
        </button>
    </div>

    <div id="mobile-menu" x-show="open" x-cloak x-transition.origin.top class="border-t border-slate-100 bg-white shadow-pop-lg lg:hidden">
        <nav class="container-page grid gap-1 py-4" aria-label="Mobile">
            @foreach ([['lawyers.index', 'Find a Lawyer', 'search'], ['for-lawyers', 'For Lawyers', 'briefcase'], ['blog.index', 'Blog', 'newspaper'], ['about', 'About', 'info'], ['contact', 'Contact', 'chat']] as [$r, $l, $i])
                <a href="{{ route($r) }}" class="flex items-center gap-3 rounded-2xl px-3 py-3 text-base font-bold text-ink hover:bg-slate-50"><span class="tile size-9 bg-brand-50 text-brand-600"><x-icon :name="$i" class="size-5"/></span>{{ $l }}</a>
            @endforeach
            <div class="mt-3 grid gap-2 border-t border-slate-100 pt-4">
                @auth
                    <a href="{{ auth()->user()->homeRoute() }}" class="btn-ink py-3">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-outline w-full py-3">Log out</button></form>
                @else
                    <a href="{{ route('register') }}" class="btn-accent py-3">Join as a Lawyer</a>
                    <a href="{{ route('login') }}" class="btn-outline py-3">Lawyer log in</a>
                @endauth
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $site['company_phone']) }}" class="btn-primary py-3"><x-icon name="phone" class="size-4"/> {{ $site['company_phone'] }}</a>
            </div>
        </nav>
    </div>
</header>

<main id="main" class="flex-1">
    {{ $slot }}
</main>

<footer class="relative mt-24 overflow-hidden bg-ink text-slate-300">
    <div class="pointer-events-none absolute -top-40 -left-24 size-[28rem] rounded-full bg-brand-500/25 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-24 -bottom-40 size-[26rem] rounded-full bg-coral-500/20 blur-3xl"></div>
    <div class="container-page relative grid gap-10 py-16 sm:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <x-logo light/>
            <p class="mt-5 max-w-sm text-sm leading-6 text-slate-400">A law firm and India's growing network of verified advocates. Search freely, then talk to our team – we connect you with the right lawyer while keeping everyone's number private.</p>
            <div class="mt-6 flex flex-wrap gap-2">
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $site['company_phone']) }}" class="btn bg-white/10 text-white ring-1 ring-white/15 hover:bg-white/15"><x-icon name="phone" class="size-4"/> {{ $site['company_phone'] }}</a>
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $site['company_whatsapp']) }}" target="_blank" rel="noopener" class="btn bg-white/10 text-white ring-1 ring-white/15 hover:bg-white/15"><x-icon name="whatsapp" class="size-4"/> WhatsApp</a>
            </div>
            <a href="mailto:{{ $site['company_email'] }}" class="mt-4 inline-flex items-center gap-2 text-sm hover:text-white"><x-icon name="mail" class="size-4 text-brand-300"/> {{ $site['company_email'] }}</a>
        </div>
        @foreach ([
            'Explore' => [[route('lawyers.index'), 'Find a lawyer'], [route('lawyers.index', ['area' => 'criminal-law']), 'Criminal lawyers'], [route('lawyers.index', ['area' => 'family-divorce']), 'Divorce lawyers'], [route('lawyers.index', ['area' => 'property-real-estate']), 'Property lawyers'], [route('blog.index'), 'Blog'], [route('about'), 'About us'], [route('contact'), 'Contact']],
            'For lawyers' => [[route('for-lawyers'), 'Why join'], [route('register'), 'Register free'], [route('login'), 'Lawyer log in'], [route('legal', 'lawyer-terms'), 'Listing terms']],
            'Legal' => collect(\App\Http\Controllers\PageController::LEGAL)->except('lawyer-terms')->map(fn ($l, $s) => [route('legal', $s), $l])->values()->all(),
        ] as $heading => $links)
            <div>
                <h2 class="text-xs font-extrabold tracking-[0.18em] text-white uppercase">{{ $heading }}</h2>
                <ul class="mt-5 space-y-3 text-sm">
                    @foreach ($links as [$href, $label])<li><a href="{{ $href }}" class="transition hover:text-white">{{ $label }}</a></li>@endforeach
                </ul>
            </div>
        @endforeach
    </div>
    <div class="relative border-t border-white/10">
        <div class="container-page flex flex-col gap-3 py-7 text-xs text-slate-500 md:flex-row md:items-center md:justify-between">
            <p class="max-w-3xl">© {{ date('Y') }} {{ $site['legal_entity_name'] }}. All rights reserved. "MAT LEGAL NETWORK", the logo, website design, text, data compilation and software are protected under the Copyright Act, 1957 and applicable trademark laws. Unauthorised copying, scraping or reproduction is prohibited.</p>
            <p class="max-w-md md:text-right">MAT LEGAL NETWORK is a law firm. Advocates listed in the directory practise independently and are responsible for their own advice.</p>
        </div>
    </div>
</footer>

{{-- Bar Council of India compliant disclaimer (shown once, on public pages only) --}}
@if ($disclaimer)
<div x-data="disclaimer" x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-end justify-center bg-ink/60 p-4 backdrop-blur-sm sm:items-center" role="dialog" aria-modal="true" aria-labelledby="disclaimer-title" data-testid="disclaimer">
    <div class="w-full max-w-lg rounded-[28px] bg-white p-6 shadow-pop-lg sm:p-7">
        <div class="flex items-center gap-3">
            <span class="tile size-12 bg-coral-100 text-coral-600"><x-icon name="scale" class="size-6"/></span>
            <div><p class="text-xs font-bold tracking-wider text-coral-600 uppercase">Before you continue</p><h2 id="disclaimer-title" class="text-xl">Disclaimer</h2></div>
        </div>
        <div class="mt-4 max-h-[50vh] space-y-3 overflow-y-auto text-sm leading-6 text-slate-600">
            <p>As per the rules of the Bar Council of India, advocates are not permitted to solicit work or advertise. By clicking <strong>“I agree”</strong>, you confirm that:</p>
            <ul class="list-disc space-y-1 pl-5">
                <li>You are visiting this website of your own accord to seek information about advocates, and there has been no advertisement, solicitation, invitation or inducement of any sort.</li>
                <li>Information on this website is general information only and is not legal advice on your matter. Advocate profiles are provided by the advocates themselves.</li>
                <li>{{ $site['legal_entity_name'] }} is a law firm and gives legal advice only when you engage it for your matter. Advocates listed in the directory practise independently. {{ $site['legal_entity_name'] }} is not responsible for any action taken on the basis of information on this website.</li>
                <li>We use only essential cookies needed for the website to work. See our <a class="text-brand-700 underline" href="{{ route('legal', 'cookies') }}">Cookie Policy</a>.</li>
            </ul>
        </div>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ route('legal', 'disclaimer') }}" class="btn-outline">Read full disclaimer</a>
            <button type="button" class="btn-primary" @click="accept()" data-testid="disclaimer-accept">I agree</button>
        </div>
    </div>
</div>
@endif
</body>
</html>
