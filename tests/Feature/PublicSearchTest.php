<?php

namespace Tests\Feature;

use App\Enums\LawyerStatus;
use App\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_with_search_and_disclaimer(): void
    {
        $this->lawyer();
        $this->get('/')->assertOk()
            ->assertSee('Legal help,', false)
            ->assertSee('data-testid="hero-search"', false)
            ->assertSee('Bar Council of India', false)
            ->assertSee('Join as a Lawyer');
    }

    public function test_only_approved_lawyers_are_listed(): void
    {
        $approved = $this->lawyer(LawyerStatus::Approved, ['full_name' => 'Adv. Visible Person']);
        $this->lawyer(LawyerStatus::Pending, ['full_name' => 'Adv. Pending Person']);
        $this->lawyer(LawyerStatus::Rejected, ['full_name' => 'Adv. Rejected Person']);
        $this->lawyer(LawyerStatus::Suspended, ['full_name' => 'Adv. Suspended Person']);

        $this->get('/lawyers')->assertOk()
            ->assertSee($approved->full_name)
            ->assertDontSee('Pending Person')
            ->assertDontSee('Rejected Person')
            ->assertDontSee('Suspended Person')
            ->assertSeeInOrder(['data-testid="results-count">', '1<']);
    }

    public function test_private_contact_details_are_never_rendered_publicly(): void
    {
        $l = $this->lawyer(LawyerStatus::Approved, [
            'phone' => '9123456789', 'alternate_phone' => '9988776655',
            'office_address' => 'Secret Chambers, 12 Hidden Lane', 'enrolment_number' => 'SECRET/999/2001', 'pincode' => '400099',
        ]);
        $email = $l->user->email;

        foreach (['/lawyers', '/lawyers/'.$l->slug, '/', '/sitemap.xml'] as $url) {
            $this->get($url)->assertOk()
                ->assertDontSee('9123456789')->assertDontSee('9988776655')
                ->assertDontSee('Secret Chambers')->assertDontSee('SECRET/999/2001')
                ->assertDontSee('400099')->assertDontSee($email);
        }
        // Serialising a profile never leaks private fields either.
        $json = json_encode($l->fresh()->toArray());
        $this->assertStringNotContainsString('9123456789', $json);
        $this->assertStringNotContainsString('SECRET/999/2001', $json);
    }

    public function test_profile_page_shows_company_contact_and_profile_id(): void
    {
        $l = $this->lawyer();
        $this->get('/lawyers/'.$l->slug)->assertOk()
            ->assertSee($l->full_name)
            ->assertSee($l->public_code)
            ->assertSee('data-testid="call-company"', false)
            ->assertSee('data-testid="whatsapp-company"', false)
            ->assertSee('data-testid="enquiry-form"', false)
            ->assertSee('wa.me/'.config('mat.settings_defaults.company_whatsapp'), false)
            ->assertSee(rawurlencode($l->public_code), false);
    }

    public function test_unpublished_profiles_return_404(): void
    {
        foreach ([LawyerStatus::Pending, LawyerStatus::Rejected, LawyerStatus::Suspended] as $s) {
            $l = $this->lawyer($s);
            $this->get('/lawyers/'.$l->slug)->assertNotFound();
        }
        $this->get('/lawyers/does-not-exist')->assertNotFound();
    }

    public function test_profile_views_are_counted(): void
    {
        $l = $this->lawyer();
        $this->get('/lawyers/'.$l->slug);
        $this->get('/lawyers/'.$l->slug);
        $this->assertSame(2, $l->fresh()->profile_views);
    }

    public function test_filter_by_state_and_city(): void
    {
        $mumbai = $this->lawyer(attrs: ['city' => 'mumbai', 'full_name' => 'Adv. Mumbai One']);
        $pune = $this->lawyer(attrs: ['city' => 'pune', 'full_name' => 'Adv. Pune One']);
        $delhi = $this->lawyer(attrs: ['city' => 'new-delhi', 'full_name' => 'Adv. Delhi One']);

        $this->get('/lawyers?state=maharashtra')->assertSee($mumbai->full_name)->assertSee($pune->full_name)->assertDontSee($delhi->full_name)
            ->assertSee('Lawyers in Maharashtra');
        $this->get('/lawyers?state=maharashtra&city=pune')->assertSee($pune->full_name)->assertDontSee($mumbai->full_name)
            ->assertSee('Lawyers in Pune, Maharashtra');
        // City without state still works.
        $this->get('/lawyers?city=new-delhi')->assertSee($delhi->full_name)->assertDontSee($pune->full_name);
        // City that does not belong to the state returns nothing rather than wrong results.
        $this->get('/lawyers?state=delhi&city=pune')->assertDontSee($pune->full_name)->assertSee('data-testid="empty-state"', false);
    }

    public function test_filter_by_practice_area_language_and_court(): void
    {
        $a = $this->lawyer(attrs: ['full_name' => 'Adv. Alpha'], areas: ['criminal-law'], languages: ['hindi'], courts: ['high-court']);
        $b = $this->lawyer(attrs: ['full_name' => 'Adv. Bravo'], areas: ['family-divorce'], languages: ['english'], courts: ['family-court']);

        $this->get('/lawyers?area=criminal-law')->assertSee('Adv. Alpha')->assertDontSee('Adv. Bravo')->assertSee('Criminal Law Lawyers');
        $this->get('/lawyers?language=english')->assertSee('Adv. Bravo')->assertDontSee('Adv. Alpha');
        $this->get('/lawyers?court=high-court')->assertSee('Adv. Alpha')->assertDontSee('Adv. Bravo');
        $this->get('/lawyers?area=criminal-law&language=english')->assertDontSee('Adv. Alpha')->assertDontSee('Adv. Bravo');
    }

    public function test_filter_by_experience_gender_online_and_fee(): void
    {
        $year = now()->year;
        $senior = $this->lawyer(attrs: ['full_name' => 'Adv. Senior', 'practice_since' => $year - 20, 'gender' => 'male', 'offers_online_consultation' => false, 'consultation_fee' => 5000]);
        $junior = $this->lawyer(attrs: ['full_name' => 'Adv. Junior', 'practice_since' => $year - 2, 'gender' => 'female', 'offers_online_consultation' => true, 'consultation_fee' => 500]);

        $this->get('/lawyers?experience=10')->assertSee('Adv. Senior')->assertDontSee('Adv. Junior');
        $this->get('/lawyers?experience=1')->assertSee('Adv. Senior')->assertSee('Adv. Junior');
        $this->get('/lawyers?gender=female')->assertSee('Adv. Junior')->assertDontSee('Adv. Senior');
        $this->get('/lawyers?online=1')->assertSee('Adv. Junior')->assertDontSee('Adv. Senior');
        $this->get('/lawyers?fee_max=1000')->assertSee('Adv. Junior')->assertDontSee('Adv. Senior');
    }

    public function test_sorting(): void
    {
        $year = now()->year;
        $this->lawyer(attrs: ['full_name' => 'Adv. Zed Young', 'practice_since' => $year - 1]);
        $this->lawyer(attrs: ['full_name' => 'Adv. Amy Old', 'practice_since' => $year - 30]);

        $this->get('/lawyers?sort=experience')->assertSeeInOrder(['Adv. Amy Old', 'Adv. Zed Young']);
        $this->get('/lawyers?sort=name')->assertSeeInOrder(['Adv. Amy Old', 'Adv. Zed Young']);
    }

    public function test_garbage_input_is_ignored_safely(): void
    {
        $this->lawyer(attrs: ['full_name' => 'Adv. Safe']);
        $this->get('/lawyers?experience=abc&sort=DROP%20TABLE&gender=robot&fee_max=xyz&online=')->assertOk()->assertSee('Adv. Safe');
        $this->get('/lawyers?state=atlantis')->assertOk()->assertSee('data-testid="empty-state"', false);
        $this->get('/lawyers?q[]=array&state[]=x')->assertOk();
        $this->get('/lawyers?q='.urlencode("' OR 1=1 -- "))->assertOk();
        $this->get('/lawyers?q='.str_repeat('a', 5000))->assertOk();
        $this->get('/lawyers?q='.urlencode('+++***((("@'))->assertOk();
    }

    public function test_results_are_paginated(): void
    {
        foreach (range(1, 15) as $i) {
            $this->lawyer(attrs: ['full_name' => "Adv. Person $i"]);
        }
        $page1 = $this->get('/lawyers')->assertOk();
        $this->assertSame(12, substr_count($page1->getContent(), 'data-testid="lawyer-card"'));
        $page2 = $this->get('/lawyers?page=2')->assertOk();
        $this->assertSame(3, substr_count($page2->getContent(), 'data-testid="lawyer-card"'));
        $this->get('/lawyers?page=99')->assertOk();   // empty page, but valid
        $this->get('/lawyers?page=101')->assertNotFound(); // anti-scraping cap
    }

    public function test_short_name_search_uses_like_fallback(): void
    {
        $this->lawyer(attrs: ['full_name' => 'Adv. Om Prakash']);
        $this->lawyer(attrs: ['full_name' => 'Adv. Ravi Teja']);
        $this->get('/lawyers?q=Om')->assertSee('Adv. Om Prakash')->assertDontSee('Adv. Ravi Teja');
    }

    public function test_cities_endpoint_returns_cities_for_state(): void
    {
        $this->getJson('/locations/maharashtra/cities')->assertOk()->assertJsonFragment(['name' => 'Pune', 'slug' => 'pune']);
        $state = State::where('slug', 'karnataka')->first();
        $this->getJson('/locations/'.$state->id.'/cities')->assertOk()->assertJsonFragment(['name' => 'Bengaluru']);
        $this->getJson('/locations/nowhere/cities')->assertOk()->assertExactJson([]);
    }

    public function test_search_is_rate_limited(): void
    {
        for ($i = 0; $i < 90; $i++) {
            $this->get('/locations/goa/cities')->assertOk();
        }
        $this->get('/lawyers')->assertStatus(429);
    }
}
