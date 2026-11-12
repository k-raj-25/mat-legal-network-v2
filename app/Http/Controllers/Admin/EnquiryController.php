<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Enquiry;
use App\Models\LawyerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EnquiryController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $q = Enquiry::query()->with(['lawyer:id,full_name,public_code', 'assignedLawyer:id,full_name,public_code', 'practiceArea:id,name'])->latest();
        if ($status && EnquiryStatus::tryFrom($status)) {
            $q->where('status', $status);
        }
        if ($term = trim((string) $request->query('q'))) {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('reference', $term)->orWhere('email', 'like', $like));
        }

        return view('admin.enquiries.index', [
            'enquiries' => $q->paginate(25)->withQueryString(),
            'status' => $status,
            'counts' => Enquiry::toBase()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function show(Enquiry $enquiry)
    {
        $enquiry->load(['lawyer.city', 'lawyer.practiceAreas', 'assignedLawyer.city', 'assignedLawyer.practiceAreas', 'practiceArea', 'spamReviewer:id,name']);

        return view('admin.enquiries.show', ['enquiry' => $enquiry]);
    }

    /**
     * Admin decision on enquiries the AI held back. Works for one enquiry or a whole page at once.
     * "spam" confirms it; "not_spam" puts it back in the normal New flow.
     */
    public function reviewSpam(Request $request)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['spam', 'not_spam'])],
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer'],
        ]);
        $confirm = $data['decision'] === 'spam';

        $enquiries = Enquiry::whereIn('id', $data['ids'])->where('status', EnquiryStatus::SuspectedSpam->value)->get();
        if ($enquiries->isEmpty()) {
            return back()->withErrors(['decision' => 'These enquiries are no longer waiting for a spam review.']);
        }

        DB::transaction(function () use ($enquiries, $confirm) {
            foreach ($enquiries as $enquiry) {
                $enquiry->forceFill([
                    'status' => $confirm ? EnquiryStatus::Spam : EnquiryStatus::New,
                    'spam_review' => $confirm ? 'confirmed' : 'not_spam',
                    'spam_reviewed_by' => auth()->id(),
                    'spam_reviewed_at' => now(),
                ])->save();
                AuditLog::record($confirm ? 'enquiry.spam_confirmed' : 'enquiry.spam_cleared', $enquiry, ['reference' => $enquiry->reference]);
            }
        });

        $n = $enquiries->count();
        $what = $n === 1 ? 'Enquiry '.$enquiries->first()->reference : $n.' enquiries';

        return back()->with('status', $confirm ? "{$what} confirmed as spam." : "{$what} marked not spam and moved to New.");
    }

    public function update(Request $request, Enquiry $enquiry)
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(EnquiryStatus::class)->only(
                $enquiry->status === EnquiryStatus::SuspectedSpam ? EnquiryStatus::cases() : EnquiryStatus::manual()
            )],
            'assigned_code' => ['nullable', 'string', 'max:20'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $assigned = null;
        if (filled($data['assigned_code'] ?? null)) {
            $assigned = LawyerProfile::published()->where('public_code', strtoupper(trim($data['assigned_code'])))->first();
            if (! $assigned) {
                return back()->withInput()->withErrors(['assigned_code' => 'No approved lawyer found with that profile ID.']);
            }
        }

        $newStatus = EnquiryStatus::from($data['status']);
        if ($enquiry->status === EnquiryStatus::SuspectedSpam && $newStatus !== EnquiryStatus::SuspectedSpam) {
            // Moving it out by hand counts as the spam review too.
            $enquiry->spam_review = $newStatus === EnquiryStatus::Spam ? 'confirmed' : 'not_spam';
            $enquiry->spam_reviewed_by = auth()->id();
            $enquiry->spam_reviewed_at = now();
        }
        $enquiry->status = $newStatus;
        $enquiry->assigned_lawyer_id = $assigned?->id;
        $enquiry->admin_notes = $data['admin_notes'] ?? null;
        $enquiry->save();

        AuditLog::record('enquiry.updated', $enquiry, ['status' => $enquiry->status->value, 'assigned' => $assigned?->public_code]);

        return back()->with('status', 'Enquiry updated.');
    }
}
