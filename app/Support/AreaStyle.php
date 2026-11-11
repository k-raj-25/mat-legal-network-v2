<?php

namespace App\Support;

/** Icon + colour tile for each practice area (vibrant, rotating palette for unknown ones). */
class AreaStyle
{
    private const TONES = [
        'teal' => 'bg-brand-100 text-brand-700',
        'coral' => 'bg-coral-100 text-coral-700',
        'violet' => 'bg-violet-100 text-violet-700',
        'amber' => 'bg-amber-100 text-amber-700',
        'sky' => 'bg-sky-100 text-sky-700',
        'pink' => 'bg-pink-100 text-pink-700',
        'lime' => 'bg-lime-100 text-lime-700',
        'indigo' => 'bg-indigo-100 text-indigo-700',
    ];

    private const MAP = [
        'criminal-law' => ['shield', 'coral'],
        'family-divorce' => ['heart', 'pink'],
        'civil-litigation' => ['scale', 'teal'],
        'property-real-estate' => ['home', 'amber'],
        'corporate-commercial' => ['building', 'indigo'],
        'cheque-bounce-ni-act' => ['cash', 'lime'],
        'consumer-protection' => ['users', 'sky'],
        'labour-employment' => ['briefcase', 'violet'],
        'tax-income-tax-gst' => ['receipt', 'amber'],
        'banking-finance' => ['cash', 'teal'],
        'intellectual-property' => ['bulb', 'violet'],
        'cyber-crime-it' => ['cpu', 'sky'],
        'constitutional-writs' => ['flag', 'coral'],
        'wills-trusts-succession' => ['document', 'indigo'],
        'motor-accident-claims' => ['truck', 'amber'],
        'medical-negligence' => ['heart', 'coral'],
        'arbitration-mediation' => ['chat', 'teal'],
        'immigration-visa' => ['globe', 'sky'],
        'insolvency-bankruptcy' => ['bolt', 'pink'],
        'service-matters' => ['briefcase', 'lime'],
        'environmental-law' => ['sparkles', 'lime'],
        'documentation-agreements' => ['document', 'teal'],
        'startup-business-advisory' => ['rocket', 'violet'],
    ];

    /** @return array{0: string, 1: string} [icon name, tile classes] */
    public static function for(string $slug): array
    {
        [$icon, $tone] = self::MAP[$slug] ?? ['scale', array_keys(self::TONES)[crc32($slug) % count(self::TONES)]];

        return [$icon, self::TONES[$tone]];
    }
}
