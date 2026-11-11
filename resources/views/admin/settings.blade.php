<x-layouts.admin title="Settings">
    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-3xl space-y-6">
        @csrf @method('PUT')
        <section class="card p-6">
            <h2 class="font-sans font-semibold">Company contact (shown to clients)</h2>
            <p class="mt-1 text-sm text-slate-500">These appear on every lawyer profile instead of the lawyer's own number.</p>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <x-input name="company_phone" label="Helpline number" required :value="$values['company_phone']" help="Shown as written, e.g. +91 98765 43210"/>
                <x-input name="company_whatsapp" label="WhatsApp number" required :value="$values['company_whatsapp']" help="Digits only with country code, e.g. 919876543210"/>
                <x-input name="company_email" type="email" label="Support email" required :value="$values['company_email']"/>
                <x-input name="support_hours" label="Support hours" :value="$values['support_hours']"/>
                <x-input name="company_address" type="textarea" rows="2" label="Registered address" required :value="$values['company_address']" class="sm:col-span-2"/>
            </div>
        </section>
        <section class="card p-6">
            <h2 class="font-sans font-semibold">Legal details (used in Terms &amp; Privacy Policy)</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <x-input name="legal_entity_name" label="Legal entity name" required :value="$values['legal_entity_name']" help="e.g. MAT Legal Network Private Limited"/>
                <x-input name="jurisdiction_city" label="Jurisdiction city" required :value="$values['jurisdiction_city']" help="Courts of this city have jurisdiction under the Terms."/>
                <x-input name="grievance_officer_name" label="Grievance Officer name" required :value="$values['grievance_officer_name']"/>
                <x-input name="grievance_officer_email" type="email" label="Grievance Officer email" required :value="$values['grievance_officer_email']"/>
            </div>
        </section>
        <button class="btn-primary">Save settings</button>
    </form>
</x-layouts.admin>
