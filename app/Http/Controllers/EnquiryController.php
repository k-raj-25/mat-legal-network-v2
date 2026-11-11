<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnquiryRequest;
use App\Models\Enquiry;
use App\Models\LawyerProfile;
use App\Notifications\NewEnquiryReceived;
use App\Rules\PhoneNumber;
use App\Support\SpamGuard;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class EnquiryController extends Controller
{
    public function store(EnquiryRequest $request)
    {
        $lawyer = null;
        if ($request->filled('lawyer')) {
            $lawyer = LawyerProfile::published()->where('public_code', $request->input('lawyer'))->first();
        }
        $back = $lawyer ? route('lawyers.show', $lawyer).'#enquiry' : route('contact').'#enquiry';

        // Bots get the same success screen but nothing is stored.
        if (SpamGuard::looksLikeBot($request)) {
            return redirect($back)->with('enquiry_success', 'ENQ-'.strtoupper(Str::random(7)));
        }

        $data = $request->validated();
        $enquiry = Enquiry::create([
            ...collect($data)->except('consent')->all(),
            'phone' => PhoneNumber::normalize($data['phone']),
            'source' => $lawyer ? 'profile' : 'contact',
            'lawyer_profile_id' => $lawyer?->id,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 490, ''),
            'consented_at' => now(),
        ]);

        if ($to = config('mat.admin.notify_email')) {
            Notification::route('mail', $to)->notify(new NewEnquiryReceived($enquiry));
        }

        return redirect($back)->with('enquiry_success', $enquiry->reference);
    }
}
