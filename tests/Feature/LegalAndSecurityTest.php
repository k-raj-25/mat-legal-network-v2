<?php

namespace Tests\Feature;

use App\Enums\LawyerStatus;
use App\Http\Controllers\PageController;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_legal_pages_render_with_key_clauses(): void
    {
        $expect = [
            'terms' => ['not a law firm', 'scraper', 'Copyright Act, 1957', 'Sections 43 and 66', 'exclusive jurisdiction'],
            'privacy' => ['Digital Personal Data Protection Act, 2023', 'Data Fiduciary', 'never displayed publicly', 'withdraw consent', 'Grievance Officer'],
            'disclaimer' => ['Rule 36', 'not</strong> an advertisement', 'no advertisement, personal communication, solicitation'],
            'lawyer-terms' => ['State Bar Council', 'free of charge', 'confidential personal data', 'Rule 36'],
            'cookies' => ['strictly necessary', 'XSRF-TOKEN', 'mln_disclaimer'],
            'intellectual-property' => ['Trade Marks Act, 1999', 'Section 2(o)', 'Section 63', 'train any competing'],
            'grievance' => ['24 hours', '15 days', 'Grievance Appellate Committee'],
        ];
        $this->assertSame(array_keys(PageController::LEGAL), array_keys($expect));
        foreach ($expect as $page => $phrases) {
            $res = $this->get('/legal/'.$page)->assertOk();
            foreach ($phrases as $p) {
                $res->assertSee($p, false);
            }
        }
    }

    public function test_footer_links_to_every_legal_page_and_shows_copyright(): void
    {
        $res = $this->get('/about')->assertOk();
        foreach (array_keys(PageController::LEGAL) as $page) {
            $res->assertSee(route('legal', $page), false);
        }
        $res->assertSee('All rights reserved')->assertSee('Unauthorised copying, scraping or reproduction is prohibited');
    }

    public function test_static_pages(): void
    {
        foreach (['/about', '/for-lawyers', '/contact'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/nope-not-here')->assertNotFound()->assertSee('Page not found');
    }

    public function test_security_headers(): void
    {
        $this->get('/')->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Referrer-Policy');
        $this->actingAs($this->admin())->get('/admin')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString('no-store', $this->get('/admin')->headers->get('Cache-Control'));
    }

    public function test_robots_and_sitemap(): void
    {
        $approved = $this->lawyer();
        $pending = $this->lawyer(LawyerStatus::Pending);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('User-agent: GPTBot')->assertSee('Sitemap: '.route('sitemap'))
            ->assertDontSee("Disallow: /lawyers\n");
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')
            ->assertSee(route('lawyers.show', $approved->slug))->assertDontSee($pending->slug);
    }

    public function test_csrf_protection_is_active_outside_tests(): void
    {
        $this->assertContains(PreventRequestForgery::class, app(Kernel::class)->getMiddlewareGroups()['web']);
    }
}
