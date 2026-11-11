<?php

namespace Tests\Feature;

use App\Enums\LawyerStatus;
use App\Models\Enquiry;
use App\Models\PracticeArea;
use App\Notifications\NewEnquiryReceived;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EnquiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_client_can_request_callback_for_a_lawyer(): void
    {
        $l = $this->lawyer();
        $res = $this->from('/lawyers/'.$l->slug)->post('/enquiries', $this->enquiryPayload(['lawyer' => $l->public_code, 'phone' => '+91-98111 22233']));
        $res->assertRedirect(route('lawyers.show', $l).'#enquiry');

        $e = Enquiry::sole();
        $this->assertSame($l->id, $e->lawyer_profile_id);
        $this->assertSame('9811122233', $e->phone);
        $this->assertSame('profile', $e->source);
        $this->assertNotNull($e->consented_at);
        $this->assertMatchesRegularExpression('/^ENQ-[A-Z0-9]{7}$/', $e->reference);
        Notification::assertSentTo(new AnonymousNotifiable, NewEnquiryReceived::class);

        $this->get('/lawyers/'.$l->slug)->assertSee($e->reference)->assertSee('data-testid="enquiry-success"', false);
    }

    public function test_general_enquiry_from_contact_page(): void
    {
        $this->get('/contact')->assertOk()->assertSee('data-testid="enquiry-form"', false);
        $this->post('/enquiries', $this->enquiryPayload(['practice_area_id' => PracticeArea::first()->id]))->assertRedirect(route('contact').'#enquiry');
        $this->assertSame('contact', Enquiry::sole()->source);
        $this->assertNull(Enquiry::sole()->lawyer_profile_id);
    }

    public function test_enquiry_for_unpublished_lawyer_is_not_linked(): void
    {
        $l = $this->lawyer(LawyerStatus::Suspended);
        $this->post('/enquiries', $this->enquiryPayload(['lawyer' => $l->public_code]));
        $this->assertNull(Enquiry::sole()->lawyer_profile_id);
    }

    public function test_validation(): void
    {
        $this->post('/enquiries', $this->enquiryPayload(['name' => '', 'phone' => '123', 'message' => 'short', 'consent' => null, 'preferred_contact' => 'pigeon']))
            ->assertSessionHasErrors(['name', 'phone', 'message', 'consent', 'preferred_contact']);
        $this->post('/enquiries', $this->enquiryPayload(['preferred_contact' => 'email', 'email' => '']))->assertSessionHasErrors('email');
        $this->assertSame(0, Enquiry::count());
    }

    public function test_honeypot_and_fast_bots_are_silently_dropped(): void
    {
        $this->post('/enquiries', $this->enquiryPayload([SpamGuard::HONEYPOT => 'http://spam.example']))->assertSessionHas('enquiry_success');
        $this->post('/enquiries', $this->enquiryPayload([SpamGuard::TIMER => SpamGuard::token()]))->assertSessionHas('enquiry_success'); // submitted instantly
        $this->post('/enquiries', $this->enquiryPayload([SpamGuard::TIMER => 'forged']))->assertSessionHas('enquiry_success');
        $this->assertSame(0, Enquiry::count());
        Notification::assertNothingSent();
    }

    public function test_enquiries_are_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/enquiries', $this->enquiryPayload())->assertRedirect();
        }
        $this->post('/enquiries', $this->enquiryPayload())->assertStatus(429);
        $this->assertSame(5, Enquiry::count());
    }
}
