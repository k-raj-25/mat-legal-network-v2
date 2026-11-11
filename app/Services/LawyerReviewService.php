<?php

namespace App\Services;

use App\Enums\LawyerStatus;
use App\Models\AuditLog;
use App\Models\LawyerProfile;
use App\Notifications\LawyerStatusChanged;

class LawyerReviewService
{
    public function approve(LawyerProfile $lawyer): void
    {
        $this->transition($lawyer, LawyerStatus::Approved, null, 'lawyer.approved');
    }

    public function reject(LawyerProfile $lawyer, string $reason): void
    {
        $this->transition($lawyer, LawyerStatus::Rejected, $reason, 'lawyer.rejected');
    }

    public function suspend(LawyerProfile $lawyer, string $reason): void
    {
        $this->transition($lawyer, LawyerStatus::Suspended, $reason, 'lawyer.suspended');
    }

    private function transition(LawyerProfile $lawyer, LawyerStatus $to, ?string $reason, string $action): void
    {
        $from = $lawyer->status;
        $lawyer->status = $to;
        $lawyer->status_reason = $reason;
        $lawyer->reviewed_by = auth()->id();
        $lawyer->reviewed_at = now();
        if ($to === LawyerStatus::Approved) {
            $lawyer->approved_at ??= now();
        }
        $lawyer->save();

        AuditLog::record($action, $lawyer, ['from' => $from->value, 'to' => $to->value, 'reason' => $reason, 'lawyer' => $lawyer->full_name]);

        $lawyer->user->notify(new LawyerStatusChanged($lawyer));
    }
}
