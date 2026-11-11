<x-layouts.app title="For Lawyers – Join Free" description="Advocates: create a free, verified MAT LEGAL NETWORK profile and receive client enquiries without exposing your personal number.">
    <x-page-hero eyebrow="For advocates · Free listing" title="Be found by clients who need you." lead="Create a free verified profile. Clients discover you by city, practice area and language – and our team passes on genuine enquiries. Your personal number stays private." tone="coral">
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('register') }}" class="btn-accent px-7 py-3.5 text-base">Register free <x-icon name="arrow-right" class="size-5"/></a>
            <a href="{{ route('login') }}" class="btn-outline px-7 py-3.5 text-base">Lawyer log in</a>
        </div>
    </x-page-hero>
    <section class="container-page">
        <ol class="grid gap-5 md:grid-cols-3">
            @foreach ([['Register', 'Fill in your professional details and upload Bar enrolment proof and ID – about 5 minutes.', 'bg-brand-500'], ['Get verified', 'Our team checks your details, usually within 2 working days.', 'bg-coral-500'], ['Go live', 'Your profile appears in search. We call you when a client asks for you.', 'bg-violet-500']] as $i => [$t, $d, $tone])
                <li class="card p-7">
                    <span class="tile size-12 {{ $tone }} font-display text-xl font-extrabold text-white">{{ $i + 1 }}</span>
                    <h2 class="mt-5 text-2xl">{{ $t }}</h2>
                    <p class="mt-2 leading-7 text-slate-600">{{ $d }}</p>
                </li>
            @endforeach
        </ol>
    </section>
    <section class="container-page mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([['cash', 'Free listing', 'No subscription or listing fee.', 'bg-lime-100 text-lime-700'], ['lock', 'Privacy protected', 'Your phone and address are never published.', 'bg-sky-100 text-sky-700'], ['bolt', 'Edit anytime', 'Update your profile whenever you like – changes go live instantly.', 'bg-amber-100 text-amber-700'], ['scale', 'BCI-conscious', 'Factual profiles only – no advertising or solicitation.', 'bg-pink-100 text-pink-700']] as [$icon, $t, $d, $tone])
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6"><span class="tile size-11 {{ $tone }}"><x-icon :name="$icon" class="size-6"/></span><h3 class="mt-4 text-lg">{{ $t }}</h3><p class="mt-1 text-sm text-slate-600">{{ $d }}</p></div>
        @endforeach
    </section>
</x-layouts.app>
