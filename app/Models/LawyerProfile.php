<?php

namespace App\Models;

use App\Enums\LawyerStatus;
use Database\Factories\LawyerProfileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LawyerProfile extends Model
{
    /** @use HasFactory<LawyerProfileFactory> */
    use HasFactory;

    /**
     * Fields that must never leave the server on a public page or API response.
     * Clients reach lawyers only through the company.
     */
    public const PRIVATE_FIELDS = [
        'phone', 'alternate_phone', 'office_address', 'pincode',
        'enrolment_number', 'bar_council', 'date_of_birth', 'user_id',
        'status_reason', 'reviewed_by',
    ];

    /** Columns that are safe to load for public listing / profile pages. */
    public const PUBLIC_COLUMNS = [
        'id', 'public_code', 'slug', 'full_name', 'gender', 'designation', 'firm_name',
        'practice_since', 'education', 'bio', 'consultation_fee', 'offers_online_consultation',
        'photo_path', 'country_id', 'state_id', 'city_id', 'status', 'is_featured', 'approved_at',
    ];

    /** Fields a lawyer can edit (also the set we diff for the change log). */
    public const EDITABLE_FIELDS = [
        'full_name', 'gender', 'designation', 'firm_name', 'practice_since', 'education', 'bio',
        'consultation_fee', 'offers_online_consultation', 'state_id', 'city_id',
        'phone', 'alternate_phone', 'office_address', 'pincode', 'enrolment_number', 'bar_council',
    ];

    protected $fillable = [
        ...self::EDITABLE_FIELDS,
        'country_id', 'date_of_birth', 'photo_path',
    ];

    protected $hidden = self::PRIVATE_FIELDS;

    protected function casts(): array
    {
        return [
            'status' => LawyerStatus::class,
            'offers_online_consultation' => 'boolean',
            'is_featured' => 'boolean',
            'practice_since' => 'integer',
            'consultation_fee' => 'integer',
            'date_of_birth' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $lawyer) {
            $lawyer->public_code ??= static::generatePublicCode();
            $lawyer->slug ??= Str::slug($lawyer->full_name).'-'.Str::lower(Str::after($lawyer->public_code, '-'));
        });
    }

    public static function generatePublicCode(): string
    {
        do {
            // Avoid characters that are easily confused when read out over the phone.
            $code = 'MLN-'.strtr(strtoupper(Str::random(6)), ['0' => '7', 'O' => 'P', 'I' => 'J', '1' => '8', 'L' => 'M']);
        } while (static::where('public_code', $code)->exists());

        return $code;
    }

    // ---- Relations ----

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function practiceAreas(): BelongsToMany
    {
        return $this->belongsToMany(PracticeArea::class, 'lawyer_practice_area');
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'language_lawyer');
    }

    public function courts(): BelongsToMany
    {
        return $this->belongsToMany(Court::class, 'court_lawyer');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(LawyerDocument::class);
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(ProfileChangeLog::class)->latest();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ---- Scopes ----

    /** Only lawyers who may appear on the public website. */
    public function scopePublished(Builder $q): Builder
    {
        return $q->where('lawyer_profiles.status', LawyerStatus::Approved->value);
    }

    // ---- Helpers ----

    public function experienceYears(): int
    {
        return max(0, (int) now()->year - (int) $this->practice_since);
    }

    public function experienceLabel(): string
    {
        $y = $this->experienceYears();

        return $y < 1 ? 'Less than 1 year' : $y.' '.Str::plural('year', $y);
    }

    public function isPublished(): bool
    {
        return $this->status === LawyerStatus::Approved;
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim(preg_replace('/^(adv\.?|advocate)\s+/i', '', $this->full_name)));

        return Str::upper(Str::substr($parts[0] ?? '', 0, 1).Str::substr(count($parts) > 1 ? end($parts) : '', 0, 1));
    }

    public function locationLabel(): string
    {
        return trim(($this->city?->name ?? '').', '.($this->state?->name ?? ''), ', ');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
