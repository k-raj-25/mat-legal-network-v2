<?php

namespace Tests\Feature;

use App\Enums\LawyerStatus;
use App\Models\Language;
use App\Models\PracticeArea;
use App\Models\ProfileChangeLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LawyerPortalTest extends TestCase
{
    use RefreshDatabase;

    private function updatePayload($lawyer, array $overrides = []): array
    {
        return array_merge([
            'full_name' => $lawyer->full_name,
            'gender' => $lawyer->gender,
            'designation' => 'Advocate',
            'phone' => $lawyer->phone,
            'enrolment_number' => $lawyer->enrolment_number,
            'bar_council' => $lawyer->bar_council,
            'practice_since' => $lawyer->practice_since,
            'state_id' => $lawyer->state_id,
            'city_id' => $lawyer->city_id,
            'practice_areas' => $lawyer->practiceAreas->pluck('id')->all(),
            'languages' => $lawyer->languages->pluck('id')->all(),
            'courts' => [],
            'bio' => $lawyer->bio,
            'education' => $lawyer->education,
            'firm_name' => $lawyer->firm_name,
            'consultation_fee' => $lawyer->consultation_fee,
            'office_address' => $lawyer->office_address,
            'pincode' => $lawyer->pincode,
            'offers_online_consultation' => $lawyer->offers_online_consultation ? '1' : '0',
        ], $overrides);
    }

    public function test_dashboard_reflects_each_status(): void
    {
        $cases = [
            [LawyerStatus::Pending, 'awaiting verification'],
            [LawyerStatus::Approved, 'View public profile'],
            [LawyerStatus::Suspended, 'hidden from the website'],
        ];
        foreach ($cases as [$status, $text]) {
            $l = $this->lawyer($status);
            $this->actingAs($l->user)->get('/lawyer')->assertOk()->assertSee($text)->assertSee($l->public_code);
        }
        $r = $this->lawyer(LawyerStatus::Rejected);
        $r->forceFill(['status_reason' => 'Upload a clearer certificate'])->save();
        $this->actingAs($r->user)->get('/lawyer')->assertSee('Upload a clearer certificate')->assertSee('Update &amp; resubmit', false);
    }

    public function test_approved_lawyer_edits_go_live_and_are_logged(): void
    {
        $l = $this->lawyer(LawyerStatus::Approved, ['bio' => 'Old bio', 'consultation_fee' => 1000]);
        $newArea = PracticeArea::where('slug', 'cyber-crime-it')->first();

        $this->actingAs($l->user)->put('/lawyer/profile', $this->updatePayload($l, [
            'bio' => 'Brand new bio text',
            'consultation_fee' => 2500,
            'practice_areas' => [...$l->practiceAreas->pluck('id')->all(), $newArea->id],
            'city_id' => $this->city('thane')->id,
        ]))->assertRedirect(route('lawyer.dashboard'))->assertSessionHas('status', 'Your profile has been updated.');

        $l->refresh();
        $this->assertSame(LawyerStatus::Approved, $l->status, 'stays live');
        $this->get('/lawyers/'.$l->slug)->assertOk()->assertSee('Brand new bio text')->assertSee('Cyber Crime')->assertSee('Thane');

        $log = ProfileChangeLog::where('lawyer_profile_id', $l->id)->sole();
        // MySQL JSON columns don't keep key order, so compare without it.
        $this->assertEquals(['old' => 'Old bio', 'new' => 'Brand new bio text'], $log->changes['bio']);
        $this->assertSame('2500', $log->changes['consultation_fee']['new']);
        $this->assertSame('Thane', $log->changes['city_id']['new']);
        $this->assertStringContainsString('Cyber Crime', $log->changes['practice_areas']['new']);
        $this->assertNull($log->reviewed_at);
        $this->assertSame($l->user_id, $log->user_id);
    }

    public function test_saving_without_changes_creates_no_log(): void
    {
        $l = $this->lawyer();
        $this->actingAs($l->user)->put('/lawyer/profile', $this->updatePayload($l))->assertRedirect();
        $this->assertSame(0, ProfileChangeLog::count());
    }

    public function test_rejected_lawyer_can_resubmit(): void
    {
        $l = $this->lawyer(LawyerStatus::Rejected);
        $this->actingAs($l->user)->put('/lawyer/profile', $this->updatePayload($l, ['resubmit' => '1', 'education' => 'LL.M.']))
            ->assertSessionHas('status', 'Your profile has been resubmitted for review.');
        $this->assertSame(LawyerStatus::Pending, $l->fresh()->status);
        $this->assertArrayHasKey('status', ProfileChangeLog::sole()->changes);
    }

    public function test_lawyer_cannot_change_own_status_or_feature_flag(): void
    {
        $l = $this->lawyer(LawyerStatus::Pending);
        $this->actingAs($l->user)->put('/lawyer/profile', $this->updatePayload($l, ['status' => 'approved', 'is_featured' => '1', 'resubmit' => '1']));
        $this->assertSame(LawyerStatus::Pending, $l->fresh()->status);
        $this->assertFalse($l->fresh()->is_featured);
    }

    public function test_document_upload_is_logged_and_private(): void
    {
        Storage::fake('local');
        $l = $this->lawyer();
        $this->actingAs($l->user)->put('/lawyer/profile', $this->updatePayload($l, [
            'enrolment_certificate' => UploadedFile::fake()->create('new-cert.pdf', 100, 'application/pdf'),
        ]))->assertRedirect();
        $doc = $l->documents()->sole();
        Storage::disk('local')->assertExists($doc->path);
        $this->assertStringContainsString('Uploaded', ProfileChangeLog::sole()->changes['documents']['new']);

        // Owner can view, another lawyer cannot, guest cannot.
        $this->actingAs($l->user)->get(route('lawyer.documents.show', $doc))->assertOk();
        $this->actingAs($this->lawyer()->user)->get(route('lawyer.documents.show', $doc))->assertForbidden();
    }

    public function test_validation_on_update(): void
    {
        $l = $this->lawyer();
        $other = $this->lawyer();
        $this->actingAs($l->user)->put('/lawyer/profile', $this->updatePayload($l, ['enrolment_number' => $other->enrolment_number, 'languages' => [], 'phone' => 'abc']))
            ->assertSessionHasErrors(['enrolment_number', 'languages', 'phone']);
        // Keeping own enrolment number is fine.
        $this->actingAs($l->user)->put('/lawyer/profile', $this->updatePayload($l))->assertSessionHasNoErrors();
    }

    public function test_password_change(): void
    {
        $l = $this->lawyer();
        $this->actingAs($l->user)->put('/lawyer/password', ['current_password' => 'wrong', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])->assertSessionHasErrors('current_password');
        $this->actingAs($l->user)->put('/lawyer/password', ['current_password' => 'password', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])->assertSessionHas('status');
        $this->assertTrue(\Hash::check('NewPass123', $l->user->fresh()->password));
    }

    public function test_access_control(): void
    {
        $this->get('/lawyer')->assertRedirect('/login');
        $this->get('/lawyer/profile')->assertRedirect('/login');
        $this->actingAs($this->admin())->get('/lawyer')->assertForbidden();
        $this->actingAs($this->lawyer()->user)->get('/admin')->assertForbidden();
    }

    public function test_edit_page_prefills_existing_values(): void
    {
        $l = $this->lawyer(attrs: ['education' => 'LL.M. Constitutional Law'], languages: ['english', 'hindi']);
        $hindi = Language::where('slug', 'hindi')->first();
        $this->actingAs($l->user)->get('/lawyer/profile')->assertOk()
            ->assertSee('LL.M. Constitutional Law')
            ->assertSee('value="'.$hindi->id.'" class="form-check" checked', false);
    }
}
