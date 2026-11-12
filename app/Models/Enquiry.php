<?php

namespace App\Models;

use App\Enums\EnquiryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Enquiry extends Model
{
    protected $fillable = [
        'source', 'lawyer_profile_id', 'practice_area_id', 'name', 'phone', 'email', 'city',
        'preferred_contact', 'message', 'ip_address', 'user_agent', 'consented_at',
    ];

    protected $casts = [
        'status' => EnquiryStatus::class,
        'consented_at' => 'datetime',
        'spam_confidence' => 'integer',
        'spam_checked_at' => 'datetime',
        'spam_reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $e) {
            do {
                $ref = 'ENQ-'.strtoupper(Str::random(7));
            } while (static::where('reference', $ref)->exists());
            $e->reference ??= $ref;
        });
    }

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(LawyerProfile::class, 'lawyer_profile_id');
    }

    public function assignedLawyer(): BelongsTo
    {
        return $this->belongsTo(LawyerProfile::class, 'assigned_lawyer_id');
    }

    public function practiceArea(): BelongsTo
    {
        return $this->belongsTo(PracticeArea::class);
    }

    public function spamReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'spam_reviewed_by');
    }
}
