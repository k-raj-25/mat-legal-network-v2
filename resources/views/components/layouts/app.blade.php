@props(['title' => null, 'description' => null, 'noindex' => false, 'canonical' => null, 'disclaimer' => true])
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
    <meta name="theme-color" content="#142540">
    <meta name="color-scheme" content="light">
    @if ($noindex)<meta name="robots" content="noindex, nofollow">@endif
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    <meta property="og:site_name" content="MAT LEGAL NETWORK">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    <meta name="copyright" content="© {{ date('Y') }} {{ $site['legal_entity_name'] }}. All rights reserved.">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>
<body class="flex min-h-screen flex-col bg-white">
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2">Skip to content</a>

{{-- Top contact strip --}}
<div class="hidden bg-brand-950 text-xs text-brand-100 sm:block">
    <div class="container-page flex h-9 items-center justify-between">
        <p class="flex items-center gap-2"><x-icon name="shield" class="size-4 text-gold-300"/> Verified lawyers · Your contact details stay private</p>
        <p class="flex items-center gap-4">
            <span class="hidden md:inline">{{ $site['support_hours'] }}</span>
            <a href="tel:{{ preg_replace('/[^\d+]/', '', $site['company_phone']) }}" class="inline-flex items-center gap-1.5 font-semibold text-white hover:text-gold-200"><x-icon name="phone" class="size-3.5"/> {{ $site['company_phone'] }}</a>
        </p>
    </div>
</div>

<header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
    <div class="container-page flex h-16 items-center justify-between gap-4">
        <a href="{{ route('home') }}" aria-label="MAT LEGAL NETWORK home"><x-logo/></a>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="Main">
            @foreach ([['lawyers.index', 'Find a Lawyer'], ['for-lawyers', 'For Lawyers'], ['about', 'About Us'], ['contact', 'Contact']] as [$r, $l])
                <a href="{{ route($r) }}" class="rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs($r.'*') ? 'text-brand-800 bg-brand-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">{{ $l }}</a>
            @endforeach
        </nav>

        <div class="hidden items-center gap-2 lg:flex">
            @auth
                <a href="{{ auth()->user()->homeRoute() }}" class="btn-outline">My dashboard</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-ghost">Log out</button></form>
            @else
                <a href="{{ route('login') }}" class="btn-ghost">Lawyer login</a>
                <a href="{{ route('register') }}" class="btn-gold">Join as a Lawyer</a>
            @endauth
        </div>

        <button type="button" class="btn-ghost -mr-2 lg:hidden" @click="open = !open" :aria-expanded="open" aria-controls="mobile-menu" aria-label="Toggle menu">
            <x-icon name="menu" class="size-6" x-show="!open"/>
            <x-icon name="x" class="size-6" x-show="open" x-cloak/>
        </button>
    </div>

    <div id="mobile-menu" x-show="open" x-cloak x-transition.opacity class="border-t border-slate-200 bg-white lg:hidden">
        <nav class="container-page grid gap-1 py-3" aria-label="Mobile">
            @foreach ([['lawyers.index', 'Find a Lawyer'], ['for-lawyers', 'For Lawyers'], ['about', 'About Us'], ['contact', 'Contact']] as [$r, $l])
                <a href="{{ route($r) }}" class="rounded-lg px-3 py-2.5 text-base font-medium text-slate-700 hover:bg-slate-50">{{ $l }}</a>
            @endforeach
            <div class="mt-2 grid gap-2 border-t border-slate-100 pt-3">
                @auth
                    <a href="{{ auth()->user()->homeRoute() }}" class="btn-outline">My dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-ghost w-full">Log out</button></form>
                @else
                    <a href="{{ route('register') }}" class="btn-gold">Join as a Lawyer</a>
                    <a href="{{ route('login') }}" class="btn-outline">Lawyer login</a>
                @endauth
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $site['company_phone']) }}" class="btn-primary"><x-icon name="phone" class="size-4"/> {{ $site['company_phone'] }}</a>
            </div>
        </nav>
    </div>
</header>

<main id="main" class="flex-1">
    {{ $slot }}
</main>

<footer class="mt-16 bg-brand-950 text-brand-100">
    <div class="container-page grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <x-logo light/>
            <p class="mt-4 max-w-sm text-sm leading-6 text-brand-200">India's growing network of verified advocates. Search freely, then speak to our team – we connect you with the right lawyer while keeping everyone's contact details private.</p>
            <div class="mt-5 grid gap-2 text-sm">
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $site['company_phone']) }}" class="inline-flex items-center gap-2 hover:text-white"><x-icon name="phone" class="size-4 text-gold-300"/> {{ $site['company_phone'] }}</a>
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $site['company_whatsapp']) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 hover:text-white"><x-icon name="whatsapp" class="size-4 text-gold-300"/> WhatsApp</a>
                <a href="mailto:{{ $site['company_email'] }}" class="inline-flex items-center gap-2 hover:text-white"><x-icon name="mail" class="size-4 text-gold-300"/> {{ $site['company_email'] }}</a>
            </div>
        </div>
        <div>
            <h2 class="font-sans text-sm font-semibold tracking-wide text-white uppercase">Explore</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="{{ route('lawyers.index') }}" class="hover:text-white">Find a lawyer</a></li>
                <li><a href="{{ route('lawyers.index', ['area' => 'criminal-law']) }}" class="hover:text-white">Criminal lawyers</a></li>
                <li><a href="{{ route('lawyers.index', ['area' => 'family-divorce']) }}" class="hover:text-white">Divorce lawyers</a></li>
                <li><a href="{{ route('lawyers.index', ['area' => 'property-real-estate']) }}" class="hover:text-white">Property lawyers</a></li>
                <li><a href="{{ route('about') }}" class="hover:text-white">About us</a></li>
                <li><a href="{{ route('contact') }}" class="hover:text-white">Contact</a></li>
            </ul>
        </div>
        <div>
            <h2 class="font-sans text-sm font-semibold tracking-wide text-white uppercase">For lawyers</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="{{ route('for-lawyers') }}" class="hover:text-white">Why join</a></li>
                <li><a href="{{ route('register') }}" class="hover:text-white">Register free</a></li>
                <li><a href="{{ route('login') }}" class="hover:text-white">Lawyer login</a></li>
                <li><a href="{{ route('legal', 'lawyer-terms') }}" class="hover:text-white">Listing terms</a></li>
            </ul>
        </div>
        <div>
            <h2 class="font-sans text-sm font-semibold tracking-wide text-white uppercase">Legal</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                @foreach (\App\Http\Controllers\PageController::LEGAL as $slug => $label)
                    @continue($slug === 'lawyer-terms')
                    <li><a href="{{ route('legal', $slug) }}" class="hover:text-white">{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="container-page flex flex-col gap-3 py-6 text-xs text-brand-300 md:flex-row md:items-center md:justify-between">
            <p>© {{ date('Y') }} {{ $site['legal_entity_name'] }}. All rights reserved. "MAT LEGAL NETWORK", the logo, website design, text, data compilation and software are protected under the Copyright Act, 1957 and applicable trademark laws. Unauthorised copying, scraping or reproduction is prohibited.</p>
            <p class="shrink-0">MAT LEGAL NETWORK is not a law firm and does not provide legal advice.</p>
        </div>
    </div>
</footer>

{{-- Bar Council of India compliant disclaimer (shown once, on public pages only) --}}
@if ($disclaimer)
<div x-data="disclaimer" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/60 p-4 sm:items-center" role="dialog" aria-modal="true" aria-labelledby="disclaimer-title" data-testid="disclaimer">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-center gap-3">
            <span class="inline-flex size-10 items-center justify-center rounded-full bg-gold-50 text-gold-600"><x-icon name="scale" class="size-5"/></span>
            <h2 id="disclaimer-title" class="text-lg font-semibold text-slate-900">Disclaimer</h2>
        </div>
        <div class="mt-4 max-h-[50vh] space-y-3 overflow-y-auto text-sm leading-6 text-slate-600">
            <p>As per the rules of the Bar Council of India, advocates are not permitted to solicit work or advertise. By clicking <strong>“I agree”</strong>, you confirm that:</p>
            <ul class="list-disc space-y-1 pl-5">
                <li>You are visiting this website of your own accord to seek information about advocates, and there has been no advertisement, solicitation, invitation or inducement of any sort.</li>
                <li>Information on this website is provided by the advocates themselves and is for informational purposes only. It is not legal advice.</li>
                <li>{{ $site['legal_entity_name'] }} is not a law firm and is not responsible for any action taken on the basis of information on this website.</li>
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
