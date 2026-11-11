<?php

namespace App\Http\Controllers;

use App\Models\LawyerProfile;
use App\Models\PracticeArea;
use App\Models\State;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __invoke()
    {
        $stats = Cache::remember('home.stats', 600, fn () => [
            'lawyers' => LawyerProfile::published()->count(),
            'cities' => LawyerProfile::published()->distinct('city_id')->count('city_id'),
            'areas' => PracticeArea::active()->count(),
        ]);

        $topCities = Cache::remember('home.top_cities', 600, fn () => DB::table('lawyer_profiles')
            ->join('cities', 'cities.id', '=', 'lawyer_profiles.city_id')
            ->join('states', 'states.id', '=', 'cities.state_id')
            ->where('lawyer_profiles.status', 'approved')
            ->groupBy('cities.id', 'cities.name', 'cities.slug', 'states.slug')
            ->orderByDesc(DB::raw('count(*)'))
            ->limit(12)
            ->get(['cities.name', 'cities.slug', 'states.slug as state_slug', DB::raw('count(*) as total')])
            ->map(fn ($r) => (array) $r)->all());
        $topCities = collect($topCities)->map(fn ($r) => (object) $r);

        $featured = LawyerProfile::query()
            ->select(LawyerProfile::PUBLIC_COLUMNS)
            ->published()->where('is_featured', true)
            ->with(['city:id,name,state_id', 'state:id,name,slug', 'practiceAreas:id,name,slug'])
            ->latest('approved_at')->limit(6)->get();

        return view('home', [
            'stats' => $stats,
            'topCities' => $topCities,
            'featured' => $featured,
            'areas' => PracticeArea::options(),
            'states' => State::active()->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }
}
