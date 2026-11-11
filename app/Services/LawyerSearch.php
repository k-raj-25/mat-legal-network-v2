<?php

namespace App\Services;

use App\Models\City;
use App\Models\Court;
use App\Models\Language;
use App\Models\LawyerProfile;
use App\Models\PracticeArea;
use App\Models\State;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Public lawyer search. Only APPROVED profiles, only public columns.
 * All filters are index-friendly (status-first composite indexes, pivot indexes, FULLTEXT for names).
 */
class LawyerSearch
{
    public const SORTS = [
        'relevance' => 'Recommended',
        'experience' => 'Most experienced',
        'newest' => 'Newly joined',
        'name' => 'Name (A–Z)',
    ];

    /** Resolved filter models (for headings, breadcrumbs, canonical URLs). */
    public array $resolved = [];

    public function __construct(private array $filters) {}

    /** Sanitize raw query-string input into a known set of filters. */
    public static function fromRequest(array $input): self
    {
        $clean = fn ($v) => is_string($v) ? trim(mb_substr($v, 0, 100)) : null;

        return new self([
            'q' => $clean($input['q'] ?? null),
            'state' => $clean($input['state'] ?? null),
            'city' => $clean($input['city'] ?? null),
            'area' => $clean($input['area'] ?? null),
            'language' => $clean($input['language'] ?? null),
            'court' => $clean($input['court'] ?? null),
            'experience' => isset($input['experience']) && array_key_exists((int) $input['experience'], config('mat.experience_buckets')) ? (int) $input['experience'] : null,
            'gender' => isset($input['gender']) && array_key_exists($input['gender'], config('mat.genders')) ? $input['gender'] : null,
            'online' => ! empty($input['online']),
            'fee_max' => isset($input['fee_max']) && is_numeric($input['fee_max']) ? max(0, (int) $input['fee_max']) : null,
            'sort' => isset($input['sort']) && array_key_exists($input['sort'], self::SORTS) ? $input['sort'] : 'relevance',
        ]);
    }

    public function filters(): array
    {
        return $this->filters;
    }

    public function activeFilterCount(): int
    {
        return collect($this->filters)->except(['sort', 'q'])->filter()->count();
    }

    public function query(): Builder
    {
        $f = $this->filters;
        $q = LawyerProfile::query()
            ->select(array_map(fn ($c) => 'lawyer_profiles.'.$c, LawyerProfile::PUBLIC_COLUMNS))
            ->published()
            ->with(['city:id,name,state_id', 'state:id,name,slug', 'practiceAreas:id,name,slug']);

        if ($f['state']) {
            $state = State::where('slug', $f['state'])->first();
            $this->resolved['state'] = $state;
            $q->where('lawyer_profiles.state_id', $state?->id ?? 0);

            if ($f['city']) {
                $city = $state ? City::where('state_id', $state->id)->where('slug', $f['city'])->first() : null;
                $this->resolved['city'] = $city;
                $q->where('lawyer_profiles.city_id', $city?->id ?? 0);
            }
        } elseif ($f['city']) {
            $cityIds = City::where('slug', $f['city'])->pluck('id');
            $this->resolved['city'] = City::where('slug', $f['city'])->first();
            $q->whereIn('lawyer_profiles.city_id', $cityIds->all() ?: [0]);
        }

        foreach (['area' => [PracticeArea::class, 'lawyer_practice_area', 'practice_area_id'],
            'language' => [Language::class, 'language_lawyer', 'language_id'],
            'court' => [Court::class, 'court_lawyer', 'court_id']] as $key => [$model, $pivot, $fk]) {
            if ($f[$key]) {
                $item = $model::where('slug', $f[$key])->first();
                $this->resolved[$key] = $item;
                $q->whereExists(fn ($sub) => $sub->select(DB::raw(1))->from($pivot)
                    ->whereColumn($pivot.'.lawyer_profile_id', 'lawyer_profiles.id')
                    ->where($pivot.'.'.$fk, $item?->id ?? 0));
            }
        }

        if ($f['experience']) {
            // practice_since <= (current year - N) keeps the index usable.
            $q->where('lawyer_profiles.practice_since', '<=', (int) now()->year - $f['experience']);
        }
        if ($f['gender']) {
            $q->where('lawyer_profiles.gender', $f['gender']);
        }
        if ($f['online']) {
            $q->where('lawyer_profiles.offers_online_consultation', true);
        }
        if ($f['fee_max'] !== null) {
            $q->whereNotNull('lawyer_profiles.consultation_fee')->where('lawyer_profiles.consultation_fee', '<=', $f['fee_max']);
        }
        if ($f['q']) {
            $this->applyKeyword($q, $f['q']);
        }

        match ($f['sort']) {
            'experience' => $q->orderBy('lawyer_profiles.practice_since')->orderBy('lawyer_profiles.id'),
            'newest' => $q->orderByDesc('lawyer_profiles.approved_at')->orderByDesc('lawyer_profiles.id'),
            'name' => $q->orderBy('lawyer_profiles.full_name'),
            default => $q->orderByDesc('lawyer_profiles.is_featured')->orderByDesc('lawyer_profiles.approved_at')->orderByDesc('lawyer_profiles.id'),
        };

        return $q;
    }

    private function applyKeyword(Builder $q, string $term): void
    {
        // Strip honorifics and boolean-mode operators.
        $term = trim(preg_replace('/^(adv\.?|advocate)\s+/i', '', $term));
        $words = array_values(array_filter(preg_split('/\s+/', preg_replace('/[+\-><()~*"@]+/', ' ', $term))));
        if (! $words) {
            return;
        }

        $useFulltext = DB::getDriverName() === 'mysql' && collect($words)->every(fn ($w) => mb_strlen($w) >= 3);

        if ($useFulltext) {
            $against = implode(' ', array_map(fn ($w) => '+'.$w.'*', $words));
            $q->whereRaw('MATCH(lawyer_profiles.full_name, lawyer_profiles.firm_name) AGAINST (? IN BOOLEAN MODE)', [$against]);
        } else {
            foreach ($words as $w) {
                $like = '%'.addcslashes($w, '%_\\').'%';
                $q->where(fn ($inner) => $inner->where('lawyer_profiles.full_name', 'like', $like)->orWhere('lawyer_profiles.firm_name', 'like', $like));
            }
        }
    }

    /** Deep pages are never needed by people (they filter instead) and are a scraping pattern. */
    public const MAX_PAGE = 100;

    public function paginate(?int $perPage = null): LengthAwarePaginator
    {
        abort_if((int) request()->query('page', 1) > self::MAX_PAGE, 404);

        return $this->query()->paginate($perPage ?? config('mat.per_page'))->withQueryString();
    }
}
