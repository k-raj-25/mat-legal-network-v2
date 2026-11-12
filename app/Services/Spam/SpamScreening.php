<?php

namespace App\Services\Spam;

use App\Enums\EnquiryStatus;
use App\Jobs\ScreenEnquiryForSpam;
use App\Models\AuditLog;
use App\Models\Enquiry;
use App\Notifications\NewEnquiryReceived;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Runs the AI spam check for a new enquiry and decides what happens next.
 * Golden rule: an enquiry is never lost. Every failure path ends with a normal "New" enquiry
 * and the usual admin alert.
 */
class SpamScreening
{
    public static function enabled(): bool
    {
        return (bool) config('mat.spam_ai.enabled') && filled(config('mat.spam_ai.api_key'));
    }

    /** Called right after the enquiry is stored. Keeps the API call off the visitor's request. */
    public static function start(Enquiry $enquiry): void
    {
        if (! self::enabled()) {
            self::notifyAdmin($enquiry);

            return;
        }

        $enquiry->forceFill(['spam_check' => 'pending'])->saveQuietly();

        try {
            // Without a background worker (QUEUE_CONNECTION=sync, e.g. local start.bat) run it after the
            // response has been sent; otherwise queue it for the worker / cron.
            config('queue.default') === 'sync'
                ? ScreenEnquiryForSpam::dispatchAfterResponse($enquiry->id)
                : ScreenEnquiryForSpam::dispatch($enquiry->id);
        } catch (\Throwable $e) {
            self::failOpen($enquiry, $e);
        }
    }

    public static function run(Enquiry $enquiry, SpamClassifier $classifier): void
    {
        if ($enquiry->spam_check !== 'pending') {
            return; // already handled (e.g. retried job)
        }

        try {
            $verdict = $classifier->classify($enquiry);
        } catch (\Throwable $e) {
            self::failOpen($enquiry, $e);

            return;
        }

        $flag = $verdict->isSpam && $verdict->confidence >= (int) config('mat.spam_ai.threshold', 80);
        $fields = [
            'spam_check' => $flag ? 'flagged' : 'passed',
            'spam_confidence' => $verdict->confidence,
            'spam_reason' => $verdict->reason,
            'spam_model' => $verdict->model,
            'spam_checked_at' => now(),
            'updated_at' => now(),
        ];

        if ($flag) {
            // Only move it if nobody has touched it in the meantime.
            $moved = Enquiry::whereKey($enquiry->id)->where('status', EnquiryStatus::New->value)->where('spam_check', 'pending')
                ->update([...$fields, 'status' => EnquiryStatus::SuspectedSpam->value]);
            if ($moved) {
                // Logged as a system action: no user, and not the visitor's IP address.
                AuditLog::create(['action' => 'enquiry.flagged_spam', 'subject_type' => Enquiry::class, 'subject_id' => $enquiry->id, 'meta' => ['confidence' => $verdict->confidence]]);

                return; // held for admin review; no new-enquiry alert
            }
            $fields['spam_check'] = 'passed';
        }

        $updated = Enquiry::whereKey($enquiry->id)->where('spam_check', 'pending')->update($fields);
        if ($updated) {
            self::notifyAdmin($enquiry->refresh());
        }
    }

    public static function failOpen(Enquiry $enquiry, \Throwable $e): void
    {
        Log::warning('AI spam check failed; enquiry handled as normal.', [
            'enquiry' => $enquiry->reference,
            'error' => $e->getMessage(),
        ]);

        $updated = Enquiry::whereKey($enquiry->id)->where('spam_check', 'pending')
            ->update(['spam_check' => 'failed', 'spam_checked_at' => now(), 'updated_at' => now()]);
        if ($updated) {
            self::notifyAdmin($enquiry->refresh());
        }
    }

    public static function notifyAdmin(Enquiry $enquiry): void
    {
        if ($to = config('mat.admin.notify_email')) {
            Notification::route('mail', $to)->notify(new NewEnquiryReceived($enquiry));
        }
    }
}
