<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LawyerDocument extends Model
{
    protected $fillable = ['type', 'path', 'original_name', 'mime', 'size'];

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(LawyerProfile::class, 'lawyer_profile_id');
    }

    public function typeLabel(): string
    {
        return config('mat.document_types.'.$this->type, ucfirst($this->type));
    }
}
