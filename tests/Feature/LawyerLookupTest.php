<?php

namespace Tests\Feature;

use App\Enums\LawyerStatus;
use App\Http\Controllers\Admin\LawyerLookupController;
use App\Models\Enquiry;
use App\Models\LawyerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LawyerLookupTest extends TestCase
{
    use RefreshDatabase;

    private function lookup(string $q = '')
    {
        return $this->getJson('/admin/lawyers/lookup?q='.urlencode($q))->assertOk();
    }

    private function codes($response): array
    {
        return collect($response->json('results'))->pluck('code')->all();
    }

    public function test_only_admins_can_use_the_lookup(): void
    {
        $this->get('/admin/lawyers/lookup')->assertRedirect('/login');
        $this->actingAs($this->lawyer()->user)->get('/admin/lawyers/lookup')->assertForbidden();
    }

    public function test_searches_approved_lawyers_by_name_code_city_and_practice_area(): void
    {
        $this->actingAs($this->admin());
        $priya = $this->lawyer(LawyerStatus::Approved, ['full_name' => 'Adv. Priya Sharma', 'city' => 'pune'], ['criminal-law']);
        $rahul = $this->lawyer(LawyerStatus::Approved, ['full_name' => 'Adv. Rahul Mehta', 'firm_name' => 'Mehta & Co', 'city' => 'mumbai'], ['family-divorce']);
        $pending = $this->lawyer(LawyerStatus::Pending, ['full_name' => 'Adv. Priya Pending']);
        $rejected = $this->lawyer(LawyerStatus::Rejected, ['full_name' => 'Adv. Priya Rejected']);

        // Name (case-insensitive, honorific ignored), multiple words.
        $this->assertSame([$priya->public_code], $this->codes($this->lookup('priya')));
        $this->assertSame([$priya->public_code], $this->codes($this->lookup('Adv. priya sharma')));
        // Profile ID: full, lower-case, without dash, and partial prefix.
        $this->assertSame([$rahul->public_code], $this->codes($this->lookup($rahul->public_code)));
        $this->assertSame([$rahul->public_code], $this->codes($this->lookup(strtolower(str_replace('-', '', $rahul->public_code)))));
        $this->assertContains($rahul->public_code, $this->codes($this->lookup(substr($rahul->public_code, 0, 6))));
        // City, practice area, firm, and combined filters.
        $this->assertSame([$priya->public_code], $this->codes($this->lookup('Pune')));
        $this->assertSame([$rahul->public_code], $this->codes($this->lookup('family')));
        $this->assertSame([$rahul->public_code], $this->codes($this->lookup('Mehta & Co')));
        $this->assertSame([$priya->public_code], $this->codes($this->lookup('sharma criminal')));
        $this->assertSame([], $this->codes($this->lookup('sharma family')));
        // Never lists lawyers who are not approved.
        $this->assertSame([], $this->codes($this->lookup('pending')));
        $this->assertSame([], $this->codes($this->lookup($pending->public_code)));
        $this->assertSame([], $this->codes($this->lookup($rejected->public_code)));

        // Response shape the picker relies on.
        $this->lookup('priya')->assertExactJson([
            'results' => [['code' => $priya->public_code, 'name' => 'Adv. Priya Sharma', 'city' => 'Pune', 'areas' => ['Criminal Law']]],
            'more' => false,
        ]);
    }

    public function test_like_wildcards_are_literal_and_odd_input_is_safe(): void
    {
        $this->actingAs($this->admin());
        $this->lawyer(LawyerStatus::Approved, ['full_name' => 'Adv. Anil Kumar']);
        $this->assertSame([], $this->codes($this->lookup('%')));
        $this->assertSame([], $this->codes($this->lookup('_')));
        $this->assertSame([], $this->codes($this->lookup('\\')));
        $this->assertSame([], $this->codes($this->lookup("' OR 1=1 --")));
        $this->lookup(str_repeat('a ', 500)); // long input is truncated, not an error
        $this->getJson('/admin/lawyers/lookup?q[]=x')->assertOk();
    }

    public function test_results_are_capped_and_flag_more(): void
    {
        $this->actingAs($this->admin());
        LawyerProfile::factory()->count(LawyerLookupController::LIMIT + 5)->status(LawyerStatus::Approved)
            ->create(['state_id' => $this->city()->state_id, 'city_id' => $this->city()->id]);

        $r = $this->lookup();
        $this->assertCount(LawyerLookupController::LIMIT, $r->json('results'));
        $this->assertTrue($r->json('more'));
        // Sorted by name.
        $names = collect($r->json('results'))->pluck('name')->all();
        $sorted = $names;
        sort($sorted, SORT_STRING | SORT_FLAG_CASE);
        $this->assertSame($sorted, $names);
    }

    public function test_enquiry_page_renders_picker_with_current_lawyer_and_saves_profile_id(): void
    {
        $admin = $this->admin();
        $requested = $this->lawyer(LawyerStatus::Approved, ['full_name' => 'Adv. Requested Person']);
        $other = $this->lawyer(LawyerStatus::Approved, ['full_name' => 'Adv. Other Person']);
        $e = Enquiry::create(['name' => 'Client', 'phone' => '9811122233', 'message' => 'Need help', 'preferred_contact' => 'call', 'consented_at' => now(), 'lawyer_profile_id' => $requested->id]);

        $this->actingAs($admin)->get("/admin/enquiries/{$e->id}")->assertOk()
            ->assertSee('role="combobox"', false)
            ->assertSee('Adv. Requested Person · '.$requested->public_code)
            ->assertSee('data-testid="lawyer-picker"', false);

        // Same field name and value format as before.
        $this->put("/admin/enquiries/{$e->id}", ['status' => 'assigned', 'assigned_code' => $other->public_code])->assertSessionHasNoErrors();
        $this->assertSame($other->id, $e->fresh()->assigned_lawyer_id);
        $this->get("/admin/enquiries/{$e->id}")->assertSee('Adv. Other Person · '.$other->public_code);

        // Blank unassigns.
        $this->put("/admin/enquiries/{$e->id}", ['status' => 'new', 'assigned_code' => ''])->assertSessionHasNoErrors();
        $this->assertNull($e->fresh()->assigned_lawyer_id);

        // A bad ID is rejected and the typed value is shown back with the error.
        $this->from("/admin/enquiries/{$e->id}")->followingRedirects()
            ->put("/admin/enquiries/{$e->id}", ['status' => 'new', 'assigned_code' => 'MLN-NOPE00'])
            ->assertSee('MLN-NOPE00')->assertSee('No approved lawyer found with that profile ID.')->assertSee('aria-invalid="true"', false);
        $this->assertNull($e->fresh()->assigned_lawyer_id);
    }
}
