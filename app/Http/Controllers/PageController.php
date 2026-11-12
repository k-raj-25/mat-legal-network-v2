<?php

namespace App\Http\Controllers;

use App\Models\PracticeArea;
use App\Support\SpamGuard;

class PageController extends Controller
{
    public const LEGAL = [
        'terms' => 'Terms of Use',
        'privacy' => 'Privacy Policy',
        'disclaimer' => 'Disclaimer',
        'lawyer-terms' => 'Lawyer Listing Terms',
        'cookies' => 'Cookie Policy',
        'intellectual-property' => 'Intellectual Property & Copyright',
        'grievance' => 'Grievance Redressal',
    ];

    /** Legal pages linked from the disclaimer popup; the popup is not shown on them so they can be read first. */
    public const DISCLAIMER_FREE = ['disclaimer', 'cookies'];

    public function about()
    {
        return view('pages.about');
    }

    public function forLawyers()
    {
        return view('pages.for-lawyers');
    }

    public function contact()
    {
        return view('pages.contact', ['areas' => PracticeArea::options(), 'formToken' => SpamGuard::token()]);
    }

    public function legal(string $page)
    {
        abort_unless(array_key_exists($page, self::LEGAL), 404);

        return view('legal.'.$page, ['title' => self::LEGAL[$page], 'page' => $page, 'updated' => '12 November 2026']);
    }
}
