<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_lawyer_and_admin_are_sent_to_their_dashboards(): void
    {
        $l = $this->lawyer();
        $this->post('/login', ['email' => strtoupper($l->user->email), 'password' => 'password'])->assertRedirect(route('lawyer.dashboard'));
        $this->assertNotNull($l->user->fresh()->last_login_at);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();

        $admin = $this->admin();
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
    }

    public function test_wrong_credentials(): void
    {
        $l = $this->lawyer();
        $this->post('/login', ['email' => $l->user->email, 'password' => 'nope'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'ghost@example.com', 'password' => 'nope'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_throttled(): void
    {
        $l = $this->lawyer();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $l->user->email, 'password' => 'bad']);
        }
        $this->post('/login', ['email' => $l->user->email, 'password' => 'password'])->assertStatus(429);
    }

    public function test_password_reset_flow(): void
    {
        Notification::fake();
        $l = $this->lawyer();
        $this->post('/forgot-password', ['email' => 'unknown@example.com'])->assertSessionHas('status'); // no enumeration
        $this->post('/forgot-password', ['email' => $l->user->email])->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($l->user, ResetPassword::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });
        $this->get('/reset-password/'.$token.'?email='.urlencode($l->user->email))->assertOk();
        $this->post('/reset-password', ['token' => $token, 'email' => $l->user->email, 'password' => 'Fresh12345', 'password_confirmation' => 'Fresh12345'])
            ->assertRedirect('/login');
        $this->post('/login', ['email' => $l->user->email, 'password' => 'Fresh12345'])->assertRedirect(route('lawyer.dashboard'));

        $this->post('/logout');
        $this->post('/reset-password', ['token' => 'bad', 'email' => $l->user->email, 'password' => 'Other12345', 'password_confirmation' => 'Other12345'])->assertSessionHasErrors('email');
    }

    public function test_create_admin_command(): void
    {
        $this->artisan('mat:create-admin', ['email' => 'Boss@Example.com'])
            ->expectsQuestion('Password (min 10 characters)', 'VeryStrong123')
            ->assertSuccessful();
        $u = User::where('email', 'boss@example.com')->sole();
        $this->assertTrue($u->isAdmin());

        $this->artisan('mat:create-admin', ['email' => 'x@example.com'])->expectsQuestion('Password (min 10 characters)', 'short')->assertFailed();
    }
}
