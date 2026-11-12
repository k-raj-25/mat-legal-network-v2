<?php

namespace App\Services\Spam;

use App\Models\Enquiry;

interface SpamClassifier
{
    /**
     * Judge one enquiry. Throws on any failure (network, timeout, refusal, malformed answer);
     * the caller then fails open and treats the enquiry as normal.
     */
    public function classify(Enquiry $enquiry): SpamVerdict;
}
