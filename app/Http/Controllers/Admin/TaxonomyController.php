<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\City;
use App\Models\Court;
use App\Models\Language;
use App\Models\PracticeArea;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** Manage the lists used in filters: practice areas, languages, courts, states and cities. */
class TaxonomyController extends Controller
{
    public const TYPES = [
        'practice-areas' => [PracticeArea::class, 'Practice areas'],
        'languages' => [Language::class, 'Languages'],
        'courts' => [Court::class, 'Courts'],
    ];

    public function index(Request $request)
    {
        $state = $request->query('state') ? State::find($request->query('state')) : null;

        return view('admin.masters.index', [
            'lists' => collect(self::TYPES)->map(fn ($t) => ['label' => $t[1], 'items' => $t[0]::withCount('lawyers')->orderBy('sort_order')->orderBy('name')->get()]),
            'states' => State::withCount('cities')->orderBy('name')->get(),
            'state' => $state,
            'cities' => $state ? $state->cities()->orderBy('name')->get() : collect(),
        ]);
    }

    public function store(Request $request, string $type)
    {
        [$model, $label] = self::TYPES[$type] ?? abort(404);
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $slug = Str::slug($data['name']);
        if ($model::where('slug', $slug)->exists()) {
            return back()->withErrors(['name' => 'That item already exists.'])->withInput();
        }
        $item = $model::create(['name' => $data['name'], 'slug' => $slug, 'sort_order' => (int) $model::max('sort_order') + 1]);
        AuditLog::record('master.created', $item, ['type' => $type, 'name' => $item->name]);

        return back()->with('status', $data['name'].' added to '.strtolower($label).'.');
    }

    public function toggle(string $type, int $id)
    {
        [$model] = self::TYPES[$type] ?? abort(404);
        $item = $model::findOrFail($id);
        $item->is_active = ! $item->is_active;
        $item->save();
        AuditLog::record('master.toggled', $item, ['type' => $type, 'active' => $item->is_active]);

        return back()->with('status', $item->name.' is now '.($item->is_active ? 'active' : 'hidden').'.');
    }

    public function storeCity(Request $request)
    {
        $data = $request->validate([
            'state_id' => ['required', 'exists:states,id'],
            'name' => ['required', 'string', 'max:100'],
        ]);
        $slug = Str::slug($data['name']);
        if (City::where('state_id', $data['state_id'])->where('slug', $slug)->exists()) {
            return back()->withErrors(['name' => 'That city already exists in this state.'])->withInput();
        }
        $city = City::create(['state_id' => $data['state_id'], 'name' => $data['name'], 'slug' => $slug]);
        Cache::forget('cities.'.$data['state_id']);
        Cache::forget('cities.'.State::find($data['state_id'])->slug);
        AuditLog::record('city.created', $city, ['name' => $city->name]);

        return redirect()->route('admin.masters.index', ['state' => $data['state_id']])->with('status', $city->name.' added.');
    }
}
