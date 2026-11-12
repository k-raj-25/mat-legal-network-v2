<?php

namespace App\Services\Spam;

final readonly class SpamVerdict
{
    /**
     * @param  int  $confidence  0-100, how sure the classifier is that the enquiry IS spam
     */
    public function __construct(
        public bool $isSpam,
        public int $confidence,
        public string $reason,
        public string $model,
    ) {}
}
