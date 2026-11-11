<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Enquiry;
use App\Models\LawyerProfile;
use Illuminate\Http\Request;
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
        $enquiry->load(['lawyer.city', 'assignedLawyer.city', 'practiceArea']);

        return view('admin.enquiries.show', ['enquiry' => $enquiry]);
    }

    public function update(Request $request, Enquiry $enquiry)
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(EnquiryStatus::class)],
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

        $enquiry->status = EnquiryStatus::from($data['status']);
        $enquiry->assigned_lawyer_id = $assigned?->id;
        $enquiry->admin_notes = $data['admin_notes'] ?? null;
        $enquiry->save();

        AuditLog::record('enquiry.updated', $enquiry, ['status' => $enquiry->status->value, 'assigned' => $assigned?->public_code]);

        return back()->with('status', 'Enquiry updated.');
    }
}
