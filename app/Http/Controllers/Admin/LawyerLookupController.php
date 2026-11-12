<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LawyerProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Autocomplete for the "Assign to lawyer" picker on the admin enquiry page.
 *
 * Only approved (published) lawyers are returned, because only they can be
 * assigned. Results are capped so the picker stays fast with any number of
 * lawyers: the browser never downloads the whole directory.
 */
class LawyerLookupController extends Controller
{
    public const LIMIT = 15;

    public function __invoke(Request $request): JsonResponse
    {
        $q = $request->query('q');
        $term = is_string($q) ? mb_substr(trim($q), 0, 80) : '';

        $query = LawyerProfile::published()
            ->select(['lawyer_profiles.id', 'public_code', 'full_name', 'firm_name', 'city_id'])
            ->with(['city:id,name', 'practiceAreas' => fn ($p) => $p->select('practice_areas.id', 'name')->orderBy('sort_order')]);

        if ($term !== '') {
            $this->applyTerm($query, $term);
        }

        $results = $query->orderBy('full_name')->orderBy('lawyer_profiles.id')->limit(self::LIMIT)->get();

        return response()->json([
            'results' => $results->map(fn (LawyerProfile $l) => [
                'code' => $l->public_code,
                'name' => $l->full_name,
                'city' => $l->city?->name,
                'areas' => $l->practiceAreas->take(2)->pluck('name')->values(),
            ]),
            // Lets the picker say "showing the first 15, keep typing" without a COUNT(*).
            'more' => $results->count() === self::LIMIT,
        ]);
    }

    private function applyTerm(Builder $q, string $term): void
    {
        // "MLN-AB3", "mln ab3k9x": match the profile ID by prefix, which uses its unique index.
        if (preg_match('/^mln[\s\-]*([a-z0-9]*)$/i', $term, $m)) {
            $q->where('public_code', 'like', 'MLN-'.strtoupper($m[1]).'%');

            return;
        }

        $term = trim(preg_replace('/^(adv\.?|advocate)\s+/i', '', $term));
        $words = array_slice(array_values(array_filter(preg_split('/\s+/', $term))), 0, 5);

        // Every word must match the name, firm, profile ID, city or a practice area.
        foreach ($words as $word) {
            $like = '%'.addcslashes($word, '%_\\').'%';
            $q->where(function (Builder $w) use ($like) {
                $w->where('full_name', 'like', $like)
                    ->orWhere('firm_name', 'like', $like)
                    ->orWhere('public_code', 'like', $like)
                    ->orWhereIn('city_id', fn ($c) => $c->select('id')->from('cities')->where('name', 'like', $like))
                    ->orWhereIn('lawyer_profiles.id', fn ($p) => $p->select('lawyer_practice_area.lawyer_profile_id')
                        ->from('lawyer_practice_area')
                        ->join('practice_areas', 'practice_areas.id', '=', 'lawyer_practice_area.practice_area_id')
                        ->where('practice_areas.name', 'like', $like));
            });
        }
    }
}
