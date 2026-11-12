<x-layouts.admin :title="'Enquiry '.$enquiry->reference">
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_380px]">
        <div class="min-w-0 space-y-6">
            <section class="card p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-xl">{{ $enquiry->name }}</h2>
                    <span class="badge {{ $enquiry->status->badgeClasses() }}">{{ $enquiry->status->label() }}</span>
                </div>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs font-semibold text-slate-500 uppercase">Phone</dt><dd class="mt-0.5"><a href="tel:{{ $enquiry->phone }}" class="font-medium text-brand-700">{{ $enquiry->phone }}</a> · <a href="https://wa.me/91{{ $enquiry->phone }}" target="_blank" rel="noopener" class="text-emerald-700">WhatsApp</a></dd></div>
                    <div><dt class="text-xs font-semibold text-slate-500 uppercase">Email</dt><dd class="mt-0.5">{{ $enquiry->email ?: '—' }}</dd></div>
                    <div><dt class="text-xs font-semibold text-slate-500 uppercase">Prefers</dt><dd class="mt-0.5">{{ ucfirst($enquiry->preferred_contact) }}</dd></div>
                    <div><dt class="text-xs font-semibold text-slate-500 uppercase">City</dt><dd class="mt-0.5">{{ $enquiry->city ?: '—' }}</dd></div>
                    <div><dt class="text-xs font-semibold text-slate-500 uppercase">Received</dt><dd class="mt-0.5">{{ $enquiry->created_at->format('d M Y, H:i') }} ({{ $enquiry->created_at->diffForHumans() }})</dd></div>
                    <div><dt class="text-xs font-semibold text-slate-500 uppercase">Source</dt><dd class="mt-0.5">{{ $enquiry->source === 'profile' ? 'Lawyer profile' : 'Contact page' }} · consent {{ $enquiry->consented_at->format('d M Y H:i') }}</dd></div>
                </dl>
                <div class="mt-5 rounded-xl bg-slate-50 p-4 text-sm leading-6 whitespace-pre-line text-slate-800">{{ $enquiry->message }}</div>
            </section>

            @if ($enquiry->lawyer)
                <section class="card p-6">
                    <h3 class="text-lg">Requested lawyer</h3>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <div><p class="font-medium">{{ $enquiry->lawyer->full_name }} <span class="text-slate-500">· {{ $enquiry->lawyer->public_code }}</span></p><p class="text-sm text-slate-500">{{ $enquiry->lawyer->city?->name }} · <a class="text-brand-700" href="tel:{{ $enquiry->lawyer->phone }}">{{ $enquiry->lawyer->phone }}</a></p></div>
                        <a href="{{ route('admin.lawyers.show', $enquiry->lawyer->slug) }}" class="btn-outline btn-sm">Open</a>
                    </div>
                </section>
            @endif
        </div>

        <aside>
            <form method="POST" action="{{ route('admin.enquiries.update', $enquiry) }}" class="card space-y-4 p-5" data-testid="enquiry-update">
                @csrf @method('PUT')
                <h3 class="text-lg">Follow-up</h3>
                <x-select name="status" label="Status" :options="collect(\App\Enums\EnquiryStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="$enquiry->status->value" :placeholder="false"/>
                <x-lawyer-picker name="assigned_code" label="Assign to lawyer" :lawyer="$enquiry->assignedLawyer ?? $enquiry->lawyer" help="{{ $enquiry->assignedLawyer ? 'Currently: '.$enquiry->assignedLawyer->full_name.' · '.$enquiry->assignedLawyer->phone : 'Leave blank if not assigned yet.' }}"/>
                <x-input name="admin_notes" type="textarea" rows="5" label="Internal notes" :value="$enquiry->admin_notes" maxlength="5000"/>
                <button class="btn-primary w-full">Save</button>
            </form>
        </aside>
    </div>
</x-layouts.admin>
