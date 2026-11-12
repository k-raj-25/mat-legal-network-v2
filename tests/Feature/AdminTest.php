<?php

namespace Tests\Feature;

use App\Enums\EnquiryStatus;
use App\Enums\LawyerStatus;
use App\Models\AuditLog;
use App\Models\City;
use App\Models\Enquiry;
use App\Models\LawyerProfile;
use App\Models\PracticeArea;
use App\Models\ProfileChangeLog;
use App\Models\State;
use App\Notifications\LawyerStatusChanged;
use App\Services\LawyerProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_area_requires_admin_role(): void
    {
        foreach (['/admin', '/admin/lawyers', '/admin/enquiries', '/admin/changes', '/admin/masters', '/admin/settings', '/admin/audit-log'] as $url) {
            $this->get($url)->assertRedirect('/login');
            $this->actingAs($this->lawyer()->user)->get($url)->assertForbidden();
            auth()->logout();
        }
    }

    public function test_every_admin_page_renders(): void
    {
        $admin = $this->admin();
        $l = $this->lawyer(LawyerStatus::Pending);
        $e = Enquiry::create(['name' => 'Test', 'phone' => '9811122233', 'message' => 'Need a lawyer please', 'preferred_contact' => 'call', 'consented_at' => now(), 'lawyer_profile_id' => $l->id]);
        $this->actingAs($admin);
        foreach (['/admin', '/admin/lawyers', '/admin/lawyers?status=all', '/admin/lawyers?status=bogus', '/admin/lawyers?q='.$l->public_code, '/admin/lawyers/'.$l->slug,
            '/admin/enquiries', '/admin/enquiries?status=new', '/admin/enquiries/'.$e->id, '/admin/changes', '/admin/changes?all=1',
            '/admin/masters', '/admin/masters?state='.State::first()->id, '/admin/settings', '/admin/audit-log'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin/lawyers/'.$l->slug)->assertSee($l->phone)->assertSee($l->enrolment_number); // admin sees private data
    }

    public function test_approve_publishes_lawyer_and_notifies(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $l = $this->lawyer(LawyerStatus::Pending, ['full_name' => 'Adv. To Approve']);
        $this->get('/lawyers')->assertDontSee('Adv. To Approve');

        $this->actingAs($admin)->post("/admin/lawyers/{$l->slug}/status", ['action' => 'approve'])->assertRedirect()->assertSessionHas('status');

        $l->refresh();
        $this->assertSame(LawyerStatus::Approved, $l->status);
        $this->assertNotNull($l->approved_at);
        $this->assertSame($admin->id, $l->reviewed_by);
        Notification::assertSentTo($l->user, LawyerStatusChanged::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'lawyer.approved', 'subject_id' => $l->id, 'user_id' => $admin->id]);

        auth()->logout();
        $this->get('/lawyers')->assertSee('Adv. To Approve');
        $this->get('/lawyers/'.$l->slug)->assertOk();
    }

    public function test_reject_and_suspend_require_reason(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $l = $this->lawyer(LawyerStatus::Pending);

        $this->actingAs($admin)->post("/admin/lawyers/{$l->slug}/status", ['action' => 'reject'])->assertSessionHasErrors('reason');
        $this->assertSame(LawyerStatus::Pending, $l->fresh()->status);

        $this->post("/admin/lawyers/{$l->slug}/status", ['action' => 'reject', 'reason' => 'Certificate unreadable']);
        $this->assertSame(LawyerStatus::Rejected, $l->fresh()->status);
        $this->assertSame('Certificate unreadable', $l->fresh()->status_reason);
        Notification::assertSentTo($l->user, LawyerStatusChanged::class, function ($n) {
            return str_contains(implode(' ', $n->toMail($n->lawyer->user)->introLines), 'needs a few changes');
        });

        $live = $this->lawyer(LawyerStatus::Approved, ['full_name' => 'Adv. Will Be Hidden']);
        $this->post("/admin/lawyers/{$live->slug}/status", ['action' => 'suspend'])->assertSessionHasErrors('reason');
        $this->post("/admin/lawyers/{$live->slug}/status", ['action' => 'suspend', 'reason' => 'Complaint received']);
        $this->assertSame(LawyerStatus::Suspended, $live->fresh()->status);
        auth()->logout();
        $this->get('/lawyers')->assertDontSee('Adv. Will Be Hidden');
        $this->get('/lawyers/'.$live->slug)->assertNotFound();

        $this->actingAs($admin)->post("/admin/lawyers/{$live->slug}/status", ['action' => 'reinstate']);
        $this->assertSame(LawyerStatus::Approved, $live->fresh()->status);
        $this->assertSame(['lawyer.suspended', 'lawyer.approved'], AuditLog::where('subject_type', LawyerProfile::class)->where('subject_id', $live->id)->orderBy('id')->pluck('action')->all());
        $this->get('/admin/lawyers/'.$live->slug)->assertSee('Complaint received');
    }

    public function test_request_changes_never_reads_as_rejected(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $l = $this->lawyer(LawyerStatus::Pending);

        $this->actingAs($admin)->post("/admin/lawyers/{$l->slug}/status", ['action' => 'reject', 'reason' => 'Please upload a clearer pic']);
        // Stored code is unchanged so existing rows and reports keep working.
        $this->assertDatabaseHas('audit_logs', ['action' => 'lawyer.rejected', 'subject_id' => $l->id]);

        $page = $this->get('/admin/lawyers/'.$l->slug)->assertOk()
            ->assertSee('Changes requested')->assertSee('Please upload a clearer pic');
        $this->assertStringNotContainsString('Rejected', $page->getContent());

        $audit = $this->get('/admin/audit-log')->assertOk()->assertSee('Changes requested')->assertSee('to: Changes requested');
        $this->assertStringNotContainsString('Rejected', $audit->getContent());
        $this->assertStringNotContainsString("to: 'rejected'", $audit->getContent());

        $this->get('/admin/lawyers?status=rejected')->assertOk()->assertSee('Changes requested')->assertDontSee('Rejected');

        auth()->logout();
        $this->actingAs($l->user)->get('/lawyer')->assertOk()->assertSee('Changes requested')->assertSee('Please upload a clearer pic')->assertDontSee('Rejected');
        $this->get('/lawyer/profile')->assertOk()->assertSee('Changes requested')->assertDontSee('Rejected');
    }

    public function test_audit_labels_cover_every_review_action(): void
    {
        $label = fn (string $action, array $meta = []) => (new AuditLog(['action' => $action, 'meta' => $meta]))->label();

        $this->assertSame('Changes requested', $label('lawyer.rejected', ['from' => 'pending', 'to' => 'rejected']));
        $this->assertSame('Approved', $label('lawyer.approved', ['from' => 'pending', 'to' => 'approved']));
        $this->assertSame('Approved', $label('lawyer.approved', ['from' => 'rejected', 'to' => 'approved']));
        $this->assertSame('Reinstated', $label('lawyer.approved', ['from' => 'suspended', 'to' => 'approved']));
        $this->assertSame('Suspended', $label('lawyer.suspended'));
        $this->assertSame('Document viewed', $label('document.viewed'));
        $this->assertSame('Something new', $label('lawyer.something_new'));
        // Enquiry status values are not lawyer statuses and must be left alone.
        $this->assertSame("status: 'closed'", (new AuditLog(['action' => 'enquiry.updated', 'meta' => ['status' => 'closed']]))->metaSummary());
    }

    public function test_invalid_action_is_rejected(): void
    {
        $l = $this->lawyer(LawyerStatus::Pending);
        $this->actingAs($this->admin())->post("/admin/lawyers/{$l->slug}/status", ['action' => 'delete'])->assertSessionHasErrors('action');
    }

    public function test_admin_can_view_private_documents_and_access_is_logged(): void
    {
        Storage::fake('local');
        $l = $this->lawyer(LawyerStatus::Pending);
        $doc = app(LawyerProfileService::class)->addDocument($l, 'id_proof', UploadedFile::fake()->create('id.pdf', 50, 'application/pdf'));

        $this->get(route('admin.documents.show', $doc))->assertRedirect('/login');
        $this->actingAs($this->admin())->get(route('admin.documents.show', $doc))->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.viewed']);
        // No public URL exists for private documents.
        $this->get('/storage/'.$doc->path)->assertNotFound();
    }

    public function test_feature_toggle_and_account_disable(): void
    {
        $admin = $this->admin();
        $l = $this->lawyer(LawyerStatus::Approved, ['full_name' => 'Adv. Star Lawyer']);
        $this->actingAs($admin)->post("/admin/lawyers/{$l->slug}/featured");
        $this->assertTrue($l->fresh()->is_featured);
        auth()->logout();
        \Cache::flush();
        $this->get('/')->assertSee('Adv. Star Lawyer')->assertSee('Featured lawyers');

        $this->actingAs($admin)->post("/admin/lawyers/{$l->slug}/account");
        $this->assertFalse($l->user->fresh()->is_active);
        auth()->logout();
        $this->post('/login', ['email' => $l->user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        // An already-logged-in disabled user is kicked out.
        $this->actingAs($l->user->fresh())->get('/lawyer')->assertRedirect('/login');
    }

    public function test_enquiry_management(): void
    {
        $admin = $this->admin();
        $lawyer = $this->lawyer();
        $pending = $this->lawyer(LawyerStatus::Pending);
        $e = Enquiry::create(['name' => 'Client', 'phone' => '9811122233', 'message' => 'Need help', 'preferred_contact' => 'call', 'consented_at' => now()]);

        $this->actingAs($admin)->put("/admin/enquiries/{$e->id}", ['status' => 'assigned', 'assigned_code' => strtolower($lawyer->public_code), 'admin_notes' => 'Called client'])
            ->assertSessionHas('status');
        $e->refresh();
        $this->assertSame(EnquiryStatus::Assigned, $e->status);
        $this->assertSame($lawyer->id, $e->assigned_lawyer_id);
        $this->assertSame('Called client', $e->admin_notes);

        $this->put("/admin/enquiries/{$e->id}", ['status' => 'assigned', 'assigned_code' => $pending->public_code])->assertSessionHasErrors('assigned_code');
        $this->put("/admin/enquiries/{$e->id}", ['status' => 'bogus'])->assertSessionHasErrors('status');

        $this->get('/admin/enquiries?q=Client')->assertSee('9811122233');
        $this->get('/admin/enquiries?status=closed')->assertDontSee('9811122233');
    }

    public function test_change_log_review(): void
    {
        $admin = $this->admin();
        $l = $this->lawyer();
        $a = ProfileChangeLog::create(['lawyer_profile_id' => $l->id, 'changes' => ['bio' => ['old' => 'x', 'new' => 'NEW BIO TEXT']]]);
        ProfileChangeLog::create(['lawyer_profile_id' => $l->id, 'changes' => ['bio' => ['old' => 'y', 'new' => 'z']]]);

        $this->actingAs($admin)->get('/admin/changes')->assertSee('NEW BIO TEXT');
        $this->get('/admin')->assertSee('Unreviewed profile changes');
        $this->post("/admin/changes/{$a->id}/review");
        $this->assertNotNull($a->fresh()->reviewed_at);
        $this->assertSame(1, ProfileChangeLog::unreviewed()->count());
        $this->post('/admin/changes/review-all');
        $this->assertSame(0, ProfileChangeLog::unreviewed()->count());
        $this->get('/admin/changes')->assertDontSee('NEW BIO TEXT')->assertSee('Nothing to review');
        $this->get('/admin/changes?all=1')->assertSee('NEW BIO TEXT');
    }

    public function test_masters_management(): void
    {
        $this->actingAs($this->admin());
        $this->post('/admin/masters/practice-areas', ['name' => 'Space Law'])->assertSessionHas('status');
        $this->assertDatabaseHas('practice_areas', ['slug' => 'space-law']);
        $this->post('/admin/masters/practice-areas', ['name' => 'Space Law'])->assertSessionHasErrors('name');
        $this->post('/admin/masters/unknown', ['name' => 'X'])->assertNotFound();

        $this->get('/admin/masters')->assertSee('Space Law');
        $this->get('/lawyers')->assertSee('Space Law');
        $id = PracticeArea::where('slug', 'space-law')->value('id');
        $this->post("/admin/masters/practice-areas/{$id}/toggle");
        $this->get('/lawyers')->assertDontSee('Space Law'); // hidden from filters (cache cleared on save)

        $state = State::where('slug', 'goa')->first();
        $this->post('/admin/masters/cities', ['state_id' => $state->id, 'name' => 'Calangute'])->assertRedirect();
        $this->assertDatabaseHas('cities', ['state_id' => $state->id, 'slug' => 'calangute']);
        $this->getJson('/locations/goa/cities')->assertJsonFragment(['name' => 'Calangute']);
        $this->post('/admin/masters/cities', ['state_id' => $state->id, 'name' => 'Calangute'])->assertSessionHasErrors('name');
        $this->assertSame(1, City::where('slug', 'calangute')->count());
    }

    public function test_settings_update_changes_public_contact_details(): void
    {
        $this->actingAs($this->admin())->put('/admin/settings', [
            'company_phone' => '+91 99999 11111', 'company_whatsapp' => '919999911111', 'company_email' => 'help@matlegal.test',
            'company_address' => 'Connaught Place, New Delhi', 'support_hours' => '24x7', 'legal_entity_name' => 'MAT Legal Network Pvt Ltd',
            'jurisdiction_city' => 'Mumbai', 'grievance_officer_name' => 'Ms. A. Rao', 'grievance_officer_email' => 'grievance@matlegal.test',
        ])->assertSessionHas('status');

        $this->put('/admin/settings', ['company_whatsapp' => '+91 abc'])->assertSessionHasErrors(['company_whatsapp', 'company_phone']);

        auth()->logout();
        $l = $this->lawyer();
        $this->get('/lawyers/'.$l->slug)->assertSee('+91 99999 11111')->assertSee('wa.me/919999911111', false)->assertSee('24x7');
        $this->get('/legal/terms')->assertSee('MAT Legal Network Pvt Ltd')->assertSee('courts at Mumbai');
        $this->get('/legal/grievance')->assertSee('Ms. A. Rao')->assertSee('grievance@matlegal.test');
    }
}
