<?php

namespace App\Http\Controllers\Lawyer;

use App\Http\Controllers\Auth\LawyerRegistrationController;
use App\Http\Controllers\Controller;
use App\Http\Requests\LawyerProfileUpdateRequest;
use App\Models\LawyerDocument;
use App\Services\LawyerProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $lawyer = $request->user()->lawyerProfile()->with(['practiceAreas', 'languages', 'courts', 'documents'])->firstOrFail();

        return view('lawyer.profile', ['lawyer' => $lawyer, ...LawyerRegistrationController::formData()]);
    }

    public function update(LawyerProfileUpdateRequest $request, LawyerProfileService $service)
    {
        $lawyer = $request->user()->lawyerProfile;
        $data = $request->validated();
        $data['offers_online_consultation'] = $request->boolean('offers_online_consultation');
        $data['courts'] ??= [];

        $service->update($lawyer, $data, $request->only('photo'), $request->boolean('resubmit'));

        foreach (['enrolment_certificate', 'id_proof', 'other'] as $type) {
            if ($request->hasFile($type)) {
                $service->addDocument($lawyer, $type, $request->file($type), log: true);
            }
        }

        $message = $request->boolean('resubmit') && $lawyer->fresh()->status->value === 'pending'
            ? 'Your profile has been resubmitted for review.'
            : 'Your profile has been updated.';

        return redirect()->route('lawyer.dashboard')->with('status', $message);
    }

    public function document(Request $request, LawyerDocument $document)
    {
        abort_unless($document->lawyer_profile_id === $request->user()->lawyerProfile?->id, 403);

        return response()->file(Storage::disk('local')->path($document->path), [
            'Content-Disposition' => 'inline; filename="'.addslashes($document->original_name).'"',
        ]);
    }

    public function password(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $request->user()->forceFill(['password' => $request->input('password')])->save();

        return back()->with('status', 'Your password has been changed.');
    }
}
