<x-layouts.app title="About Us" description="MAT LEGAL NETWORK helps people across India find verified advocates and connect with them privately through one trusted helpline.">
    <x-page-hero eyebrow="About MAT LEGAL NETWORK" title="Making good legal help easy to find." lead="We help people compare verified advocates by city, practice area, language and experience – then connect them through our own team, so nobody's personal number ends up on the internet."/>
    <section class="container-page grid gap-5 md:grid-cols-3">
        @foreach ([
            ['shield', 'Verified, not just listed', 'Every advocate submits Bar enrolment proof and ID. Our team reviews each profile before it goes live.', 'bg-brand-100 text-brand-700'],
            ['lock', 'Private by design', 'Lawyers\' numbers and addresses are never shown. Clients reach us; we connect them with the right lawyer.', 'bg-coral-100 text-coral-700'],
            ['sparkles', 'Free to join & search', 'Searching is free for clients and listing is free for lawyers while we grow the network across India.', 'bg-violet-100 text-violet-700'],
        ] as [$icon, $t, $d, $tone])
            <div class="card p-7">
                <span class="tile size-14 {{ $tone }}"><x-icon :name="$icon" class="size-7"/></span>
                <h2 class="mt-5 text-2xl">{{ $t }}</h2>
                <p class="mt-2 leading-7 text-slate-600">{{ $d }}</p>
            </div>
        @endforeach
    </section>
    <section class="container-page mt-6">
        <div class="relative overflow-hidden rounded-[32px] bg-ink p-8 text-slate-300 sm:p-12">
            <div class="pointer-events-none absolute -top-20 -right-20 size-72 rounded-full bg-coral-500/25 blur-3xl"></div>
            <h2 class="relative text-3xl text-white">What we are – and what we're not</h2>
            <p class="relative mt-4 max-w-3xl leading-7">MAT LEGAL NETWORK is an information and connection platform. We are not a law firm, we do not give legal advice and we do not rank lawyers by outcomes. Any engagement, advice and fee is strictly between you and the advocate you choose. Please read our <a href="{{ route('legal', 'disclaimer') }}" class="font-bold text-coral-300 underline underline-offset-2">Disclaimer</a>.</p>
        </div>
    </section>
</x-layouts.app>
