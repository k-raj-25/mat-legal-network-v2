@php use App\Enums\LawyerStatus; @endphp
<x-layouts.lawyer title="Edit profile">
    @if ($lawyer->status === LawyerStatus::Approved)
        <p class="mb-6 flex gap-3 rounded-2xl bg-brand-50 px-4 py-3.5 text-sm font-semibold text-brand-800 ring-1 ring-brand-200"><x-icon name="bolt" class="size-5 shrink-0"/>Changes go live immediately. For quality and safety, every change is recorded and visible to the MAT team.</p>
    @elseif ($lawyer->status === LawyerStatus::Rejected)
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900"><strong>Changes requested:</strong> {{ $lawyer->status_reason }}</div>
    @endif

    <form method="POST" action="{{ route('lawyer.profile.update') }}" enctype="multipart/form-data" class="space-y-6" data-testid="profile-form">
        @csrf @method('PUT')
        <section class="card p-6 sm:p-8"><h2 class="mb-5 text-xl">Personal details</h2>@include('lawyer.partials.personal')</section>
        <section class="card p-6 sm:p-8"><h2 class="mb-5 text-xl">Professional details</h2>@include('lawyer.partials.professional')</section>
        <section class="card p-6 sm:p-8"><h2 class="mb-5 text-xl">Location, fees &amp; about</h2>@include('lawyer.partials.location')</section>
        <section class="card p-6 sm:p-8"><h2 class="mb-5 text-xl">Photo &amp; documents</h2>@include('lawyer.partials.documents')</section>

        <div class="sticky bottom-3 z-10 flex flex-wrap items-center justify-end gap-3 rounded-full bg-white/90 p-2 shadow-pop-lg ring-1 ring-slate-200/70 backdrop-blur">
            <a href="{{ route('lawyer.dashboard') }}" class="btn-ghost">Cancel</a>
            @if ($lawyer->status === LawyerStatus::Rejected)
                <button name="resubmit" value="1" class="btn-accent" data-testid="resubmit">Save &amp; resubmit for review</button>
            @else
                <button class="btn-primary" data-testid="save-profile">Save changes</button>
            @endif
        </div>
    </form>

    <section class="card mt-8 max-w-xl p-6 sm:p-8">
        <h2 class="text-xl">Change password</h2>
        <form method="POST" action="{{ route('lawyer.password.update') }}" class="mt-4 space-y-4">
            @csrf @method('PUT')
            <x-input name="current_password" type="password" label="Current password" required autocomplete="current-password"/>
            <x-input name="password" type="password" label="New password" required autocomplete="new-password" id="new_password"/>
            <x-input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" id="new_password_confirmation"/>
            <button class="btn-outline">Update password</button>
        </form>
    </section>
</x-layouts.lawyer>
