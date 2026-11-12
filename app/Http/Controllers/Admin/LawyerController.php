<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LawyerStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LawyerDocument;
use App\Models\LawyerProfile;
use App\Services\LawyerReviewService;
use App\Support\DecisionTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LawyerController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $statuses = array_column(LawyerStatus::cases(), 'value');
        if ($status !== 'all' && ! in_array($status, $statuses, true)) {
            $status = 'pending';
        }

        $q = LawyerProfile::query()->with(['user:id,email', 'city:id,name', 'state:id,name']);
        if ($status !== 'all') {
            $q->where('status', $status);
        }
        if ($term = trim((string) $request->query('q'))) {
            $q->where(function ($w) use ($term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $w->where('full_name', 'like', $like)->orWhere('public_code', $term)
                    ->orWhere('enrolment_number', 'like', $like)->orWhere('phone', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', $like));
            });
        }
        $q->orderBy($status === 'pending' ? 'submitted_at' : 'updated_at', $status === 'pending' ? 'asc' : 'desc');

        $counts = LawyerProfile::toBase()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('admin.lawyers.index', [
            'lawyers' => $q->paginate(25)->withQueryString(),
            'status' => $status,
            'counts' => $counts,
        ]);
    }

    public function show(LawyerProfile $lawyer)
    {
        $lawyer->load(['user', 'country', 'state', 'city', 'practiceAreas', 'languages', 'courts', 'documents', 'reviewer', 'changeLogs.user']);
        $history = AuditLog::where('subject_type', LawyerProfile::class)->where('subject_id', $lawyer->id)->with('user')->latest()->get();

        return view('admin.lawyers.show', ['lawyer' => $lawyer, 'history' => DecisionTimeline::build($history, $lawyer->changeLogs)]);
    }

    public function updateStatus(Request $request, LawyerProfile $lawyer, LawyerReviewService $review)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject', 'suspend', 'reinstate'])],
            'reason' => ['nullable', 'required_if:action,reject,suspend', 'string', 'max:1000'],
        ], ['reason.required_if' => 'Please give a reason – it is emailed to the lawyer.']);

        match ($data['action']) {
            'approve', 'reinstate' => $review->approve($lawyer),
            'reject' => $review->reject($lawyer, $data['reason']),
            'suspend' => $review->suspend($lawyer, $data['reason']),
        };

        return redirect()->route('admin.lawyers.show', $lawyer)->with('status', 'Lawyer is now '.strtolower($lawyer->status->label()).'.');
    }

    public function toggleFeatured(LawyerProfile $lawyer)
    {
        $lawyer->is_featured = ! $lawyer->is_featured;
        $lawyer->save();
        AuditLog::record($lawyer->is_featured ? 'lawyer.featured' : 'lawyer.unfeatured', $lawyer, ['lawyer' => $lawyer->full_name]);

        return back()->with('status', $lawyer->is_featured ? 'Lawyer is now featured on the home page.' : 'Lawyer removed from featured.');
    }

    public function toggleAccount(LawyerProfile $lawyer)
    {
        $user = $lawyer->user;
        $user->is_active = ! $user->is_active;
        $user->save();
        AuditLog::record($user->is_active ? 'account.activated' : 'account.deactivated', $lawyer, ['lawyer' => $lawyer->full_name]);

        return back()->with('status', $user->is_active ? 'Login enabled.' : 'Login disabled. The lawyer can no longer sign in.');
    }

    public function document(LawyerDocument $document)
    {
        AuditLog::record('document.viewed', $document->lawyer, ['document' => $document->id, 'type' => $document->type]);

        return response()->file(Storage::disk('local')->path($document->path), [
            'Content-Disposition' => 'inline; filename="'.addslashes($document->original_name).'"',
        ]);
    }
}
