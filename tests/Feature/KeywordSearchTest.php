<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

/**
 * MySQL FULLTEXT indexes only see committed rows, so this class truncates
 * instead of wrapping each test in a transaction.
 */
class KeywordSearchTest extends TestCase
{
    use DatabaseTruncation;

    protected $seed = true;

    protected function tearDown(): void
    {
        // Rows here are committed; remove them so transaction-based tests stay isolated.
        User::query()->delete();
        parent::tearDown();
    }

    public function test_name_and_firm_search(): void
    {
        $this->lawyer(attrs: ['full_name' => 'Adv. Priya Sharma', 'firm_name' => null]);
        $this->lawyer(attrs: ['full_name' => 'Adv. Rohan Mehta', 'firm_name' => 'Mehta & Associates']);
        $this->lawyer(attrs: ['full_name' => 'Adv. Kavya Iyer', 'firm_name' => 'Lexwise Chambers']);

        $this->get('/lawyers?q=sharma')->assertSee('Priya Sharma')->assertDontSee('Rohan Mehta');
        $this->get('/lawyers?q=Priya+Sharma')->assertSee('Priya Sharma')->assertDontSee('Kavya Iyer');
        $this->get('/lawyers?q=Adv.+Priya')->assertSee('Priya Sharma');           // honorific stripped
        $this->get('/lawyers?q=lexwise')->assertSee('Kavya Iyer')->assertDontSee('Priya Sharma'); // firm name
        $this->get('/lawyers?q=meh')->assertSee('Rohan Mehta');                    // prefix match
        $this->get('/lawyers?q=nobodyhere')->assertSee('data-testid="empty-state"', false);
    }
}
