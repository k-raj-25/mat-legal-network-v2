@php $editing = (bool) $lawyer; $mimes = strtoupper(implode(', ', config('mat.uploads.document_mimes'))); @endphp
<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-input name="photo" type="file" label="Profile photo (optional)" accept="image/jpeg,image/png,image/webp" help="Square, professional photo. JPG/PNG/WebP up to 2 MB."/>
        @if ($editing && $lawyer->photo_path)
            <label class="mt-2 flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remove_photo" value="1" class="form-check"> Remove current photo</label>
        @endif
    </div>
    <x-input name="enrolment_certificate" type="file" :label="'Bar enrolment certificate / ID card'.($editing ? ' (replace)' : '')" :required="! $editing" accept=".pdf,.jpg,.jpeg,.png" :help="$mimes.' up to 5 MB. Kept private – only our verification team can see it.'"/>
    <x-input name="id_proof" type="file" :label="'Government photo ID'.($editing ? ' (replace)' : '')" :required="! $editing" accept=".pdf,.jpg,.jpeg,.png" help="Aadhaar (masked), PAN, passport or voter ID."/>
    <x-input name="other" type="file" label="Other supporting document (optional)" accept=".pdf,.jpg,.jpeg,.png" class="sm:col-span-2"/>
    @if ($editing && $lawyer->documents->isNotEmpty())
        <div class="sm:col-span-2">
            <p class="form-label">Uploaded documents</p>
            <ul class="divide-y divide-slate-100 rounded-lg border border-slate-200 text-sm">
                @foreach ($lawyer->documents as $doc)
                    <li class="flex items-center justify-between gap-3 px-3 py-2">
                        <span class="flex min-w-0 items-center gap-2"><x-icon name="document" class="size-4 shrink-0 text-slate-400"/> <span class="truncate">{{ $doc->typeLabel() }} – {{ $doc->original_name }}</span></span>
                        <a href="{{ route('lawyer.documents.show', $doc) }}" target="_blank" class="shrink-0 font-medium text-brand-700 hover:underline">View</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
