<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\Language;
use App\Models\LawyerProfile;
use App\Models\PracticeArea;
use App\Models\State;
use App\Services\LawyerSearch;
use App\Support\SpamGuard;
use Illuminate\Http\Request;

class LawyerDirectoryController extends Controller
{
    public function index(Request $request)
    {
        $search = LawyerSearch::fromRequest($request->query());
        $lawyers = $search->paginate();
        $f = $search->filters();
        $r = $search->resolved;

        $states = State::active()->orderBy('name')->get(['id', 'name', 'slug']);
        $cities = isset($r['state']) && $r['state']
            ? $r['state']->cities()->active()->orderBy('name')->get(['id', 'name', 'slug'])
            : collect();

        // Heading like "Criminal Law lawyers in Mumbai, Maharashtra"
        $heading = trim((($r['area'] ?? null)?->name ?? '').' Lawyers');
        $place = collect([($r['city'] ?? null)?->name, ($r['state'] ?? null)?->name])->filter()->implode(', ');
        if ($place) {
            $heading .= ' in '.$place;
        }

        return view('lawyers.index', [
            'lawyers' => $lawyers,
            'filters' => $f,
            'activeFilterCount' => $search->activeFilterCount(),
            'heading' => $heading,
            'states' => $states,
            'cities' => $cities,
            'areas' => PracticeArea::options(),
            'languages' => Language::options(),
            'courts' => Court::options(),
            'sorts' => LawyerSearch::SORTS,
        ]);
    }

    public function show(string $slug)
    {
        $lawyer = LawyerProfile::query()
            ->select(LawyerProfile::PUBLIC_COLUMNS)
            ->published()
            ->where('slug', $slug)
            ->with(['city:id,name,slug,state_id', 'state:id,name,slug', 'practiceAreas:id,name,slug', 'languages:id,name', 'courts:id,name'])
            ->firstOrFail();

        LawyerProfile::whereKey($lawyer->id)->increment('profile_views');

        $similar = LawyerProfile::query()
            ->select(LawyerProfile::PUBLIC_COLUMNS)
            ->published()
            ->where('city_id', $lawyer->city_id)
            ->whereKeyNot($lawyer->id)
            ->with(['city:id,name,state_id', 'state:id,name,slug', 'practiceAreas:id,name,slug'])
            ->inRandomOrder()->limit(3)->get();

        return view('lawyers.show', [
            'lawyer' => $lawyer,
            'similar' => $similar,
            'areas' => PracticeArea::options(),
            'formToken' => SpamGuard::token(),
        ]);
    }
}
