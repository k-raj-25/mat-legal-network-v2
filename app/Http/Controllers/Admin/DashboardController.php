<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\LawyerProfile;
use App\Models\ProfileChangeLog;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $byStatus = LawyerProfile::toBase()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('admin.dashboard', [
            'counts' => [
                'pending' => (int) ($byStatus['pending'] ?? 0),
                'approved' => (int) ($byStatus['approved'] ?? 0),
                'rejected' => (int) ($byStatus['rejected'] ?? 0),
                'suspended' => (int) ($byStatus['suspended'] ?? 0),
                'new_enquiries' => Enquiry::where('status', 'new')->count(),
                'enquiries_week' => Enquiry::where('created_at', '>=', now()->subDays(7))->count(),
                'unreviewed_changes' => ProfileChangeLog::unreviewed()->count(),
            ],
            'pending' => LawyerProfile::where('status', 'pending')->with(['city', 'state'])->oldest('submitted_at')->limit(8)->get(),
            'enquiries' => Enquiry::with('lawyer:id,full_name,public_code')->latest()->limit(8)->get(),
        ]);
    }
}
