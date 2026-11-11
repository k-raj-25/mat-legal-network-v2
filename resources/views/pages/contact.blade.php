<x-layouts.app title="Contact Us" description="Call, WhatsApp or write to MAT LEGAL NETWORK. Tell us your legal problem and we'll connect you with a suitable verified lawyer.">
    <section class="container-page grid grid-cols-1 gap-10 py-14 lg:grid-cols-[1fr_1.2fr]">
        <div>
            <h1 class="text-4xl font-bold text-brand-950">Talk to our team</h1>
            <p class="mt-4 text-lg text-slate-600">Tell us what you need help with and we'll suggest verified lawyers near you.</p>
            <x-contact-buttons class="mt-8"/>
            <dl class="mt-10 space-y-5 text-sm">
                <div class="flex gap-3"><x-icon name="mail" class="size-5 text-brand-600"/><div><dt class="font-semibold text-slate-900">Email</dt><dd><a href="mailto:{{ $site['company_email'] }}" class="text-brand-700">{{ $site['company_email'] }}</a></dd></div></div>
                <div class="flex gap-3"><x-icon name="clock" class="size-5 text-brand-600"/><div><dt class="font-semibold text-slate-900">Hours</dt><dd class="text-slate-600">{{ $site['support_hours'] }}</dd></div></div>
                <div class="flex gap-3"><x-icon name="building" class="size-5 text-brand-600"/><div><dt class="font-semibold text-slate-900">Address</dt><dd class="text-slate-600">{{ $site['company_address'] }}</dd></div></div>
            </dl>
            <p class="mt-8 text-xs text-slate-500">Complaints about the website? Contact our <a class="underline" href="{{ route('legal', 'grievance') }}">Grievance Officer</a>.</p>
        </div>
        <div class="card p-6 sm:p-8">
            <h2 class="text-2xl font-semibold text-slate-900">Request a callback</h2>
            <p class="mt-1 mb-6 text-sm text-slate-600">We usually call back within a few working hours.</p>
            <x-enquiry-form :areas="$areas" :form-token="$formToken"/>
        </div>
    </section>
</x-layouts.app>
