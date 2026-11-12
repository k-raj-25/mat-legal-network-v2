<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use Illuminate\Database\Seeder;

/** Starter blog categories (production-safe, idempotent). The admin can add or hide more. */
class BlogCategorySeeder extends Seeder
{
    public const CATEGORIES = [
        'know-your-rights' => 'Know your rights',
        'family-divorce' => 'Family & divorce',
        'criminal-law' => 'Criminal law',
        'property-real-estate' => 'Property & real estate',
        'consumer-rights' => 'Consumer rights',
        'banking-cheques' => 'Banking & cheques',
        'firm-news' => 'Firm news',
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $slug => $name) {
            BlogCategory::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }
    }
}
