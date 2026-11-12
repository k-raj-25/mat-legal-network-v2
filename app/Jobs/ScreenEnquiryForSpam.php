<?php

namespace App\Jobs;

use App\Models\Enquiry;
use App\Services\Spam\SpamClassifier;
use App\Services\Spam\SpamScreening;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ScreenEnquiryForSpam implements ShouldQueue
{
    use Queueable;

    /** API errors are handled inside (fail open), so a single attempt is enough. */
    public int $tries = 1;

    public int $timeout = 90;

    public function __construct(public int $enquiryId) {}

    public function handle(SpamClassifier $classifier): void
    {
        if ($enquiry = Enquiry::find($this->enquiryId)) {
            SpamScreening::run($enquiry, $classifier);
        }
    }

    /** Last safety net (worker killed, timeout...): release the enquiry into the normal flow. */
    public function failed(?\Throwable $e): void
    {
        if ($enquiry = Enquiry::find($this->enquiryId)) {
            SpamScreening::failOpen($enquiry, $e ?? new \RuntimeException('Spam check job failed.'));
        }
    }
}
