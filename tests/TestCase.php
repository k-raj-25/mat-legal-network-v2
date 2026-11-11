<?php

namespace Tests;

use App\Enums\LawyerStatus;
use App\Models\City;
use App\Models\Court;
use App\Models\Language;
use App\Models\LawyerProfile;
use App\Models\PracticeArea;
use App\Models\User;
use App\Support\SpamGuard;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    protected $seeder = DatabaseSeeder::class;

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    protected function city(string $slug = 'mumbai'): City
    {
        return City::where('slug', $slug)->firstOrFail();
    }

    /** Create a lawyer with relations. */
    protected function lawyer(LawyerStatus $status = LawyerStatus::Approved, array $attrs = [], array $areas = ['criminal-law'], array $languages = ['english'], array $courts = []): LawyerProfile
    {
        $city = isset($attrs['city']) ? $this->city($attrs['city']) : $this->city();
        unset($attrs['city']);
        $lawyer = LawyerProfile::factory()->status($status)->create(array_merge(['state_id' => $city->state_id, 'city_id' => $city->id], $attrs));
        $lawyer->practiceAreas()->sync(PracticeArea::whereIn('slug', $areas)->pluck('id'));
        $lawyer->languages()->sync(Language::whereIn('slug', $languages)->pluck('id'));
        $lawyer->courts()->sync(Court::whereIn('slug', $courts)->pluck('id'));

        return $lawyer->fresh();
    }

    /** Valid registration payload with fake documents. */
    protected function registrationPayload(array $overrides = []): array
    {
        $city = $this->city('pune');

        return array_merge([
            'email' => 'new.lawyer@example.com',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
            'full_name' => 'Adv. Meera Kulkarni',
            'gender' => 'female',
            'designation' => 'Advocate',
            'phone' => '+91 98765 43210',
            'enrolment_number' => 'MAH/1234/2012',
            'bar_council' => 'Bar Council of Maharashtra & Goa',
            'practice_since' => 2012,
            'education' => 'B.A. LL.B.',
            'practice_areas' => PracticeArea::whereIn('slug', ['family-divorce', 'civil-litigation'])->pluck('id')->all(),
            'languages' => Language::whereIn('slug', ['english', 'marathi'])->pluck('id')->all(),
            'courts' => Court::where('slug', 'family-court')->pluck('id')->all(),
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'office_address' => 'Shivajinagar, Pune',
            'pincode' => '411005',
            'consultation_fee' => 1500,
            'offers_online_consultation' => '1',
            'bio' => 'Family law practitioner.',
            'enrolment_certificate' => UploadedFile::fake()->create('enrolment.pdf', 300, 'application/pdf'),
            'id_proof' => UploadedFile::fake()->image('id.jpg', 600, 400),
            'photo' => UploadedFile::fake()->image('me.jpg', 400, 400),
            'accept_terms' => '1',
            'declaration' => '1',
        ], $overrides);
    }

    protected function enquiryPayload(array $overrides = []): array
    {
        $this->travel(-10)->seconds();
        $token = SpamGuard::token();
        $this->travelBack();

        return array_merge([
            'name' => 'Rahul Verma',
            'phone' => '9811122233',
            'email' => 'rahul@example.com',
            'city' => 'Mumbai',
            'preferred_contact' => 'call',
            'message' => 'I need help with a cheque bounce case.',
            'consent' => '1',
            SpamGuard::TIMER => $token,
        ], $overrides);
    }
}
