<?php

namespace Tests\Feature;

use App\Enums\EnquiryStatus;
use App\Jobs\ScreenEnquiryForSpam;
use App\Models\AuditLog;
use App\Models\Enquiry;
use App\Notifications\NewEnquiryReceived;
use App\Services\Spam\SpamCheckFailed;
use App\Services\Spam\SpamClassifier;
use App\Services\Spam\SpamVerdict;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** The AI is always faked here: these tests never call the Anthropic API. */
class SpamScreeningTest extends TestCase
{
    use RefreshDatabase;

    public array $calls = [];

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function enableAi(?SpamVerdict $verdict = null, ?\Throwable $error = null): void
    {
        config(['mat.spam_ai.api_key' => 'test-key', 'mat.spam_ai.enabled' => true, 'mat.spam_ai.threshold' => 80]);
        $test = $this;
        $this->app->instance(SpamClassifier::class, new class($test, $verdict, $error) implements SpamClassifier
        {
            public function __construct(private $test, private ?SpamVerdict $verdict, private ?\Throwable $error) {}

            public function classify(Enquiry $enquiry): SpamVerdict
            {
                $this->test->calls[] = $enquiry->id;
                if ($this->error) {
                    throw $this->error;
                }

                return $this->verdict;
            }
        });
    }

    private function spam(int $confidence = 95, string $reason = 'SEO sales pitch with links.'): SpamVerdict
    {
        return new SpamVerdict(true, $confidence, $reason, 'claude-test');
    }

    private function submit(array $overrides = []): Enquiry
    {
        $this->post('/enquiries', $this->enquiryPayload($overrides))->assertRedirect()->assertSessionHas('enquiry_success');

        return Enquiry::latest('id')->firstOrFail();
    }

    private function suspected(array $attrs = []): Enquiry
    {
        $e = Enquiry::create(['name' => 'Spammer', 'phone' => '9811122233', 'message' => 'Buy cheap backlinks now', 'preferred_contact' => 'call', 'consented_at' => now()]);
        $e->forceFill(['status' => EnquiryStatus::SuspectedSpam, 'spam_check' => 'flagged', 'spam_confidence' => 97, 'spam_reason' => 'Marketing pitch.', ...$attrs])->save();

        return $e;
    }

    // ---- Classification flow -------------------------------------------------------------

    public function test_without_api_key_enquiries_work_exactly_as_before(): void
    {
        $this->app->instance(SpamClassifier::class, new class implements SpamClassifier
        {
            public function classify(Enquiry $enquiry): SpamVerdict
            {
                throw new \LogicException('must not be called');
            }
        });

        $e = $this->submit();
        $this->assertSame(EnquiryStatus::New, $e->status);
        $this->assertNull($e->spam_check);
        Notification::assertSentTo(new AnonymousNotifiable, NewEnquiryReceived::class);
    }

    public function test_spam_switch_off_keeps_screening_off_even_with_a_key(): void
    {
        $this->enableAi($this->spam());
        config(['mat.spam_ai.enabled' => false]);
        $e = $this->submit();
        $this->assertSame([], $this->calls);
        $this->assertSame(EnquiryStatus::New, $e->status);
    }

    public function test_spam_is_held_for_admin_review_without_alert(): void
    {
        $this->enableAi($this->spam(96));
        $e = $this->submit(['message' => 'We offer SEO services, guaranteed first page rank! Visit cheap-seo.example']);

        $this->assertSame(EnquiryStatus::SuspectedSpam, $e->status);
        $this->assertSame('flagged', $e->spam_check);
        $this->assertSame(96, $e->spam_confidence);
        $this->assertSame('SEO sales pitch with links.', $e->spam_reason);
        $this->assertSame('claude-test', $e->spam_model);
        $this->assertNotNull($e->spam_checked_at);
        $this->assertNull($e->assigned_lawyer_id);
        Notification::assertNothingSent();

        $log = AuditLog::where('action', 'enquiry.flagged_spam')->sole();
        $this->assertNull($log->user_id);
        $this->assertNull($log->ip_address);
    }

    public function test_visitor_sees_the_normal_success_screen_even_for_spam(): void
    {
        $this->enableAi($this->spam());
        $res = $this->post('/enquiries', $this->enquiryPayload());
        $e = Enquiry::sole();
        $res->assertSessionHas('enquiry_success', $e->reference);
    }

    public function test_low_confidence_spam_stays_in_the_normal_flow(): void
    {
        $this->enableAi($this->spam(60, 'Slightly odd wording.'));
        $e = $this->submit();
        $this->assertSame(EnquiryStatus::New, $e->status);
        $this->assertSame('passed', $e->spam_check);
        $this->assertSame(60, $e->spam_confidence);
        Notification::assertSentTo(new AnonymousNotifiable, NewEnquiryReceived::class);
    }

    public function test_genuine_enquiry_passes_and_admin_is_alerted_once(): void
    {
        $this->enableAi(new SpamVerdict(false, 3, 'A genuine cheque bounce question.', 'claude-test'));
        $e = $this->submit();
        $this->assertSame(EnquiryStatus::New, $e->status);
        $this->assertSame('passed', $e->spam_check);
        Notification::assertSentToTimes(new AnonymousNotifiable, NewEnquiryReceived::class, 1);
    }

    public function test_api_failure_fails_open_and_is_logged(): void
    {
        Log::spy();
        $this->enableAi(error: new SpamCheckFailed('Anthropic API call failed: timeout'));
        $e = $this->submit();

        $this->assertSame(EnquiryStatus::New, $e->status);
        $this->assertSame('failed', $e->spam_check);
        Notification::assertSentTo(new AnonymousNotifiable, NewEnquiryReceived::class);
        Log::shouldHaveReceived('warning')->withArgs(fn ($msg, $ctx) => str_contains($msg, 'AI spam check failed') && $ctx['enquiry'] === $e->reference)->once();
    }

    public function test_any_unexpected_exception_also_fails_open(): void
    {
        $this->enableAi(error: new \TypeError('boom'));
        $e = $this->submit();
        $this->assertSame(EnquiryStatus::New, $e->status);
        $this->assertSame('failed', $e->spam_check);
        Notification::assertSentTo(new AnonymousNotifiable, NewEnquiryReceived::class);
    }

    public function test_crashed_job_releases_the_enquiry(): void
    {
        $this->enableAi($this->spam());
        Queue::fake();
        $e = $this->submit();
        $this->assertSame('pending', $e->spam_check);

        (new ScreenEnquiryForSpam($e->id))->failed(new \RuntimeException('worker killed'));
        $e->refresh();
        $this->assertSame(EnquiryStatus::New, $e->status);
        $this->assertSame('failed', $e->spam_check);
        Notification::assertSentTo(new AnonymousNotifiable, NewEnquiryReceived::class);
    }

    public function test_with_a_real_queue_the_check_runs_in_the_background(): void
    {
        config(['queue.default' => 'database']);
        $this->enableAi($this->spam());
        Queue::fake();

        $e = $this->submit();
        $this->assertSame('pending', $e->spam_check);
        $this->assertSame(EnquiryStatus::New, $e->status);
        $this->assertSame([], $this->calls, 'API must not be called on the visitor request');
        Queue::assertPushed(ScreenEnquiryForSpam::class, fn ($job) => $job->enquiryId === $e->id);
        Notification::assertNothingSent();

        // The worker picks it up later.
        app()->call([new ScreenEnquiryForSpam($e->id), 'handle']);
        $this->assertSame(EnquiryStatus::SuspectedSpam, $e->refresh()->status);
    }

    public function test_a_job_that_runs_twice_does_not_double_alert(): void
    {
        $this->enableAi(new SpamVerdict(false, 2, 'Genuine.', 'claude-test'));
        $e = $this->submit();
        app()->call([new ScreenEnquiryForSpam($e->id), 'handle']);
        $this->assertCount(1, $this->calls);
        Notification::assertSentToTimes(new AnonymousNotifiable, NewEnquiryReceived::class, 1);
    }

    public function test_admin_action_before_the_check_finishes_wins(): void
    {
        config(['queue.default' => 'database']);
        $this->enableAi($this->spam());
        Queue::fake();
        $e = $this->submit();

        $e->forceFill(['status' => EnquiryStatus::Contacted])->save(); // admin already called the client
        app()->call([new ScreenEnquiryForSpam($e->id), 'handle']);

        $e->refresh();
        $this->assertSame(EnquiryStatus::Contacted, $e->status);
        $this->assertSame('passed', $e->spam_check);
    }

    public function test_deleted_enquiry_job_is_a_no_op(): void
    {
        $this->enableAi($this->spam());
        app()->call([new ScreenEnquiryForSpam(999999), 'handle']);
        $this->assertSame([], $this->calls);
    }

    // ---- Admin review ----------------------------------------------------------------------

    public function test_suspected_spam_tab_shows_reason_and_confidence(): void
    {
        $admin = $this->admin();
        $this->suspected(['spam_reason' => '<script>alert(1)</script> pitch']);
        Enquiry::create(['name' => 'Real Client', 'phone' => '9811122234', 'message' => 'Need bail help', 'preferred_contact' => 'call', 'consented_at' => now()]);

        $this->actingAs($admin)->get('/admin/enquiries?status=suspected_spam')->assertOk()
            ->assertSee('data-testid="spam-queue"', false)
            ->assertSee('Spammer')->assertDontSee('Real Client')
            ->assertSee('97% spam')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt; pitch', false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->actingAs($admin)->get('/admin/enquiries')->assertOk()->assertSee('Suspected spam <span class="text-xs opacity-70">1</span>', false);
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('1 suspected spam to review');
    }

    public function test_empty_spam_queue(): void
    {
        $this->actingAs($this->admin())->get('/admin/enquiries?status=suspected_spam')->assertOk()->assertSee('No suspected spam waiting for review.');
    }

    public function test_admin_confirms_spam(): void
    {
        $admin = $this->admin();
        $e = $this->suspected();
        $this->actingAs($admin)->post('/admin/enquiries/spam-review', ['ids' => [$e->id], 'decision' => 'spam'])
            ->assertRedirect()->assertSessionHas('status', "Enquiry {$e->reference} confirmed as spam.");

        $e->refresh();
        $this->assertSame(EnquiryStatus::Spam, $e->status);
        $this->assertSame('confirmed', $e->spam_review);
        $this->assertSame($admin->id, $e->spam_reviewed_by);
        $this->assertNotNull($e->spam_reviewed_at);
        $this->assertSame(97, $e->spam_confidence, 'AI verdict is kept for the record');
        $this->assertDatabaseHas('audit_logs', ['action' => 'enquiry.spam_confirmed', 'user_id' => $admin->id, 'subject_id' => $e->id]);

        $this->actingAs($admin)->get("/admin/enquiries/{$e->id}")->assertOk()->assertSee('Confirmed as spam by '.$admin->name);
    }

    public function test_admin_marks_not_spam_and_it_returns_to_new(): void
    {
        $admin = $this->admin();
        $e = $this->suspected();
        $this->actingAs($admin)->post('/admin/enquiries/spam-review', ['ids' => [$e->id], 'decision' => 'not_spam'])
            ->assertSessionHas('status', "Enquiry {$e->reference} marked not spam and moved to New.");

        $e->refresh();
        $this->assertSame(EnquiryStatus::New, $e->status);
        $this->assertSame('not_spam', $e->spam_review);
        $this->actingAs($admin)->get('/admin/enquiries?status=new')->assertSee($e->name);
    }

    public function test_bulk_review_only_touches_suspected_spam(): void
    {
        $admin = $this->admin();
        $a = $this->suspected();
        $b = $this->suspected();
        $closed = Enquiry::create(['name' => 'Done', 'phone' => '9811122299', 'message' => 'Old case closed', 'preferred_contact' => 'call', 'consented_at' => now()]);
        $closed->forceFill(['status' => EnquiryStatus::Closed])->save();

        $this->actingAs($admin)->post('/admin/enquiries/spam-review', ['ids' => [$a->id, $b->id, $closed->id], 'decision' => 'spam'])
            ->assertSessionHas('status', '2 enquiries confirmed as spam.');
        $this->assertSame(EnquiryStatus::Spam, $a->refresh()->status);
        $this->assertSame(EnquiryStatus::Spam, $b->refresh()->status);
        $this->assertSame(EnquiryStatus::Closed, $closed->refresh()->status);
    }

    public function test_review_of_already_reviewed_enquiry_is_rejected_politely(): void
    {
        $admin = $this->admin();
        $e = $this->suspected();
        $this->actingAs($admin)->post('/admin/enquiries/spam-review', ['ids' => [$e->id], 'decision' => 'not_spam']);
        $this->actingAs($admin)->post('/admin/enquiries/spam-review', ['ids' => [$e->id], 'decision' => 'spam'])->assertSessionHasErrors('decision');
        $this->assertSame(EnquiryStatus::New, $e->refresh()->status);
    }

    public function test_review_validation_and_access(): void
    {
        $admin = $this->admin();
        $e = $this->suspected();
        $this->actingAs($admin)->post('/admin/enquiries/spam-review', ['ids' => [$e->id], 'decision' => 'delete'])->assertSessionHasErrors('decision');
        $this->actingAs($admin)->post('/admin/enquiries/spam-review', ['decision' => 'spam'])->assertSessionHasErrors('ids');
        $this->actingAs($admin)->post('/admin/enquiries/spam-review', ['ids' => range(1, 101), 'decision' => 'spam'])->assertSessionHasErrors('ids');

        auth()->logout();
        $this->post('/admin/enquiries/spam-review', ['ids' => [$e->id], 'decision' => 'spam'])->assertRedirect('/login');
        $lawyerUser = $this->lawyer()->user;
        $this->actingAs($lawyerUser)->post('/admin/enquiries/spam-review', ['ids' => [$e->id], 'decision' => 'spam'])->assertForbidden();
        $this->assertSame(EnquiryStatus::SuspectedSpam, $e->refresh()->status);
    }

    public function test_suspected_spam_cannot_be_set_by_hand(): void
    {
        $admin = $this->admin();
        $e = Enquiry::create(['name' => 'Client', 'phone' => '9811122233', 'message' => 'Need help', 'preferred_contact' => 'call', 'consented_at' => now()]);
        $this->actingAs($admin)->get("/admin/enquiries/{$e->id}")->assertOk()->assertDontSee('value="suspected_spam"', false)->assertSee('AI screening was not switched on');
        $this->actingAs($admin)->put("/admin/enquiries/{$e->id}", ['status' => 'suspected_spam'])->assertSessionHasErrors('status');
        $this->assertSame(EnquiryStatus::New, $e->refresh()->status);
    }

    public function test_moving_suspected_spam_with_the_follow_up_form_counts_as_review(): void
    {
        $admin = $this->admin();
        $e = $this->suspected();
        $this->actingAs($admin)->get("/admin/enquiries/{$e->id}")->assertOk()
            ->assertSee('data-testid="spam-panel"', false)->assertSee('Held back for your review')->assertSee('Marketing pitch.');
        $this->actingAs($admin)->put("/admin/enquiries/{$e->id}", ['status' => 'contacted'])->assertSessionHasNoErrors();
        $e->refresh();
        $this->assertSame(EnquiryStatus::Contacted, $e->status);
        $this->assertSame('not_spam', $e->spam_review);
        $this->assertSame($admin->id, $e->spam_reviewed_by);
    }

    public function test_spam_queue_page_query_count_does_not_grow_with_rows(): void
    {
        $admin = $this->admin();
        foreach (range(1, 25) as $i) {
            $this->suspected(['lawyer_profile_id' => $this->lawyer(attrs: ['phone' => '90000'.str_pad((string) $i, 5, '0', STR_PAD_LEFT)])->id]);
        }
        \DB::enableQueryLog();
        $this->actingAs($admin)->get('/admin/enquiries?status=suspected_spam')->assertOk();
        $this->assertLessThan(15, count(\DB::getQueryLog()));
    }
}
