<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_current_section_is_highlighted(): void
    {
        $this->actingAs($this->admin());
        foreach (['/admin' => 'Dashboard', '/admin/lawyers' => 'Lawyers', '/admin/audit-log' => 'Audit log', '/admin/settings' => 'Settings'] as $url => $label) {
            $html = $this->get($url)->assertOk()->getContent();
            preg_match_all('/class="flex items-center justify-between rounded-2xl[^"]*bg-white text-ink[^"]*">\s*<span[^>]*>.*?<\/svg>\s*([^<]+?)\s*<\/span>/s', $html, $m);
            $this->assertSame([$label], array_map('trim', $m[1]), "Active nav on $url");
        }
    }
}
