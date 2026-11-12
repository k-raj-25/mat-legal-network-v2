<?php

namespace App\Models;

use App\Enums\LawyerStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'meta', 'ip_address'];

    protected $casts = ['meta' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Human-readable labels for stored action codes. The codes themselves never change (old rows keep working);
     * only what admins read does. "lawyer.rejected" is the Request changes action, so it must never read as "Rejected".
     */
    public const ACTION_LABELS = [
        'lawyer.approved' => 'Approved',
        'lawyer.rejected' => 'Changes requested',
        'lawyer.suspended' => 'Suspended',
        'lawyer.featured' => 'Featured',
        'lawyer.unfeatured' => 'Removed from featured',
        'account.activated' => 'Account activated',
        'account.deactivated' => 'Account deactivated',
        'document.viewed' => 'Document viewed',
        'enquiry.updated' => 'Enquiry updated',
        'enquiry.flagged_spam' => 'Enquiry flagged as suspected spam (AI)',
        'enquiry.spam_confirmed' => 'Suspected spam confirmed',
        'enquiry.spam_cleared' => 'Marked not spam',
        'settings.updated' => 'Settings updated',
        'post.created' => 'Blog post created',
        'post.updated' => 'Blog post edited',
        'post.published' => 'Blog post published',
        'post.unpublished' => 'Blog post unpublished',
        'post.deleted' => 'Blog post deleted',
        'blog_category.created' => 'Blog category created',
        'blog_category.toggled' => 'Blog category shown/hidden',
        'editor.created' => 'Editor account created',
        'editor.updated' => 'Editor details changed',
        'editor.password_reset' => 'Editor password reset',
        'editor.activated' => 'Editor account activated',
        'editor.deactivated' => 'Editor account deactivated',
        'master.created' => 'Master item created',
        'master.toggled' => 'Master item toggled',
        'city.created' => 'City created',
    ];

    public function label(): string
    {
        // A suspended profile brought back is logged as lawyer.approved; call it what it was.
        if ($this->action === 'lawyer.approved' && ($this->meta['from'] ?? null) === LawyerStatus::Suspended->value) {
            return 'Reinstated';
        }

        return self::ACTION_LABELS[$this->action]
            ?? (string) str($this->action)->after('.')->replace('_', ' ')->ucfirst();
    }

    /** Meta as "key: value" pairs, with lawyer status codes shown by their labels (rejected → Changes requested). */
    public function metaSummary(): string
    {
        return collect($this->meta)->map(function ($v, $k) {
            if (in_array($k, ['from', 'to'], true) && is_string($v) && $s = LawyerStatus::tryFrom($v)) {
                return $k.': '.$s->label();
            }

            return $k.': '.(is_array($v) ? implode(', ', $v) : var_export($v, true));
        })->implode(' · ');
    }

    public static function record(string $action, ?Model $subject = null, array $meta = []): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'meta' => $meta ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }
}
