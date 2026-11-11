<?php

namespace App\Http\Controllers;

use App\Models\State;
use Illuminate\Support\Facades\Cache;

class LocationController extends Controller
{
    /** Cities for a state (dependent dropdowns). Accepts a state id or slug. */
    public function cities(string $state)
    {
        $cities = Cache::remember('cities.'.$state, 3600, function () use ($state) {
            $model = is_numeric($state) ? State::find($state) : State::where('slug', $state)->first();

            return $model ? $model->cities()->active()->orderBy('name')->get(['id', 'name', 'slug'])->toArray() : [];
        });

        return response()->json($cities)->header('Cache-Control', 'public, max-age=3600');
    }
}
