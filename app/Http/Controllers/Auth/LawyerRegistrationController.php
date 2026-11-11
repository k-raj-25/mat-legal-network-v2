<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LawyerRegistrationRequest;
use App\Models\Court;
use App\Models\Language;
use App\Models\PracticeArea;
use App\Models\State;
use App\Services\LawyerProfileService;
use Illuminate\Support\Facades\Auth;

class LawyerRegistrationController extends Controller
{
    public function create()
    {
        return view('auth.register', self::formData());
    }

    public function store(LawyerRegistrationRequest $request, LawyerProfileService $service)
    {
        $lawyer = $service->register(
            $request->validated(),
            $request->only(['photo', 'enrolment_certificate', 'id_proof', 'other'])
        );

        Auth::login($lawyer->user);
        $request->session()->regenerate();

        return redirect()->route('lawyer.dashboard')->with('registered', true);
    }

    public static function formData(): array
    {
        return [
            'states' => State::active()->orderBy('name')->get(['id', 'name']),
            'areas' => PracticeArea::options(),
            'languages' => Language::options(),
            'courts' => Court::options(),
            'barCouncils' => (require database_path('data/taxonomy.php'))['bar_councils'],
        ];
    }
}
