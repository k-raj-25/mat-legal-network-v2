<x-layouts.app title="Contact Us" description="Call, WhatsApp or write to MAT LEGAL NETWORK. Tell us your legal problem and we'll connect you with a suitable verified lawyer.">
    <x-page-hero eyebrow="We're here to help" title="Talk to our team" lead="Tell us what you need help with and we'll suggest verified lawyers near you."/>
    <section class="container-page grid grid-cols-1 gap-6 lg:grid-cols-[0.9fr_1.1fr]">
        <div class="space-y-4">
            <div class="relative overflow-hidden rounded-[28px] bg-gradient-to-br from-brand-500 to-brand-700 p-7 text-white">
                <div class="pointer-events-none absolute -right-12 -bottom-12 size-48 rounded-full bg-white/10"></div>
                <h2 class="text-2xl text-white">Fastest: call or WhatsApp</h2>
                <p class="mt-2 text-brand-50">{{ $site['support_hours'] }}</p>
                <x-contact-buttons class="mt-6" on-dark/>
            </div>
            @foreach ([['mail', 'Email', '<a href="mailto:'.e($site['company_email']).'" class="font-bold text-brand-700">'.e($site['company_email']).'</a>', 'bg-coral-100 text-coral-700'], ['building', 'Address', e($site['company_address']), 'bg-violet-100 text-violet-700']] as [$icon, $label, $html, $tone])
                <div class="flex gap-4 rounded-3xl border border-slate-200/80 bg-white p-5">
                    <span class="tile size-11 {{ $tone }}"><x-icon :name="$icon" class="size-5"/></span>
                    <div><p class="text-sm font-bold text-ink">{{ $label }}</p><p class="mt-0.5 text-slate-600">{!! $html !!}</p></div>
                </div>
            @endforeach
            <p class="px-2 text-xs text-slate-500">Complaints about the website? Contact our <a class="font-semibold underline" href="{{ route('legal', 'grievance') }}">Grievance Officer</a>.</p>
        </div>
        <div class="card p-6 sm:p-8">
            <h2 class="text-2xl">Request a callback</h2>
            <p class="mt-1 mb-6 text-slate-500">We usually call back within a few working hours.</p>
            <x-enquiry-form :areas="$areas" :form-token="$formToken"/>
        </div>
    </section>
</x-layouts.app>
