<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileChangeLog extends Model
{
    protected $fillable = ['lawyer_profile_id', 'user_id', 'changes', 'ip_address'];

    protected $casts = ['changes' => 'array', 'reviewed_at' => 'datetime'];

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(LawyerProfile::class, 'lawyer_profile_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnreviewed(Builder $q): Builder
    {
        return $q->whereNull('reviewed_at');
    }
}
