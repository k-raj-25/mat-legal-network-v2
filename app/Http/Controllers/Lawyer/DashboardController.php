<?php

namespace App\Http\Controllers\Lawyer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $lawyer = $request->user()->lawyerProfile()->with(['city', 'state', 'practiceAreas', 'languages', 'courts', 'documents'])->firstOrFail();

        // Simple profile-completeness meter to nudge better profiles.
        $checks = [
            'Profile photo' => (bool) $lawyer->photo_path,
            'About / bio (100+ characters)' => mb_strlen((string) $lawyer->bio) >= 100,
            'Education' => filled($lawyer->education),
            'Courts you practise in' => $lawyer->courts->isNotEmpty(),
            'Consultation fee' => $lawyer->consultation_fee !== null,
        ];

        return view('lawyer.dashboard', [
            'lawyer' => $lawyer,
            'checks' => $checks,
            'completeness' => (int) round(60 + 40 * collect($checks)->filter()->count() / count($checks)),
        ]);
    }
}
