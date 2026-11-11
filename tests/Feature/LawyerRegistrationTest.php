<?php

namespace Tests\Feature;

use App\Enums\LawyerStatus;
use App\Models\LawyerProfile;
use App\Models\PracticeArea;
use App\Models\User;
use App\Notifications\LawyerRegistrationReceived;
use App\Notifications\NewLawyerAwaitingReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LawyerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        Notification::fake();
    }

    public function test_registration_page_renders_all_steps(): void
    {
        $this->get('/register')->assertOk()
            ->assertSee('data-testid="register-form"', false)
            ->assertSee('Bar enrolment number')
            ->assertSee('Lawyer Listing Terms')
            ->assertSee('Criminal Law')
            ->assertSee('Bar Council of Delhi');
    }

    public function test_lawyer_can_register_and_lands_in_pending_state(): void
    {
        $res = $this->post('/register', $this->registrationPayload());
        $res->assertRedirect(route('lawyer.dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'new.lawyer@example.com')->firstOrFail();
        $this->assertTrue($user->isLawyer());
        $lawyer = $user->lawyerProfile;
        $this->assertSame(LawyerStatus::Pending, $lawyer->status);
        $this->assertSame('9876543210', $lawyer->phone, 'phone is normalised');
        $this->assertMatchesRegularExpression('/^MLN-[A-Z0-9]{6}$/', $lawyer->public_code);
        $this->assertCount(2, $lawyer->practiceAreas);
        $this->assertCount(2, $lawyer->languages);
        $this->assertTrue($lawyer->offers_online_consultation);
        $this->assertNotNull($lawyer->submitted_at);

        // Documents on the PRIVATE disk; photo on the public disk.
        $this->assertCount(2, $lawyer->documents);
        foreach ($lawyer->documents as $doc) {
            Storage::disk('local')->assertExists($doc->path);
            Storage::disk('public')->assertMissing($doc->path);
        }
        Storage::disk('public')->assertExists($lawyer->photo_path);

        Notification::assertSentTo($user, LawyerRegistrationReceived::class);
        Notification::assertSentTo(new AnonymousNotifiable, NewLawyerAwaitingReview::class, fn ($n, $ch, $notifiable) => $notifiable->routes['mail'] === 'alerts@mat.test');

        // The dashboard greets them (one-time flash), then: not public until approved.
        $this->get('/lawyer')->assertOk()->assertSee('Pending review')->assertSee('Registration submitted');
        $this->get('/lawyers')->assertDontSee('Meera Kulkarni');
        $this->get('/lawyers/'.$lawyer->slug)->assertNotFound();
    }

    public function test_required_fields_and_documents_are_enforced(): void
    {
        $this->post('/register', [])->assertSessionHasErrors([
            'email', 'password', 'full_name', 'phone', 'enrolment_number', 'bar_council', 'practice_since',
            'state_id', 'city_id', 'practice_areas', 'languages', 'enrolment_certificate', 'id_proof', 'accept_terms', 'declaration',
        ]);
        $this->assertGuest();
        $this->assertSame(0, LawyerProfile::count());
    }

    public function test_invalid_values_are_rejected(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $existing = $this->lawyer(attrs: ['enrolment_number' => 'DUP/1/2000']);
        $delhiCity = $this->city('new-delhi');

        $cases = [
            'phone' => ['phone' => '12345'],
            'email' => ['email' => $existing->user->email],
            'enrolment_number' => ['enrolment_number' => 'DUP/1/2000'],
            'city_id' => ['city_id' => $delhiCity->id], // not in Maharashtra
            'practice_since' => ['practice_since' => now()->year + 1],
            'pincode' => ['pincode' => '12AB'],
            'password' => ['password' => 'short', 'password_confirmation' => 'short'],
            'practice_areas' => ['practice_areas' => PracticeArea::limit(11)->pluck('id')->all()],
            'enrolment_certificate' => ['enrolment_certificate' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')],
            'id_proof' => ['id_proof' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf')],
            'photo' => ['photo' => UploadedFile::fake()->create('notimage.pdf', 10, 'application/pdf')],
            'consultation_fee' => ['consultation_fee' => -5],
        ];
        foreach ($cases as $field => $override) {
            $this->post('/register', $this->registrationPayload($override))->assertSessionHasErrors($field);
            $this->assertSame(1, LawyerProfile::count(), "case $field must not register");
            $this->assertGuest();
        }
        $this->assertSame(1, LawyerProfile::count());
    }

    public function test_status_cannot_be_injected_at_registration(): void
    {
        $this->post('/register', $this->registrationPayload(['status' => 'approved', 'is_featured' => 1, 'role' => 'admin']));
        $user = User::where('email', 'new.lawyer@example.com')->first();
        $this->assertTrue($user->isLawyer());
        $this->assertSame(LawyerStatus::Pending, $user->lawyerProfile->status);
        $this->assertFalse($user->lawyerProfile->is_featured);
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/register', [])->assertRedirect();
        }
        $this->post('/register', $this->registrationPayload())->assertStatus(429);
        $this->assertSame(0, LawyerProfile::count());
    }

    public function test_logged_in_users_cannot_open_registration(): void
    {
        $this->actingAs($this->lawyer()->user)->get('/register')->assertRedirect(route('lawyer.dashboard'));
    }
}
