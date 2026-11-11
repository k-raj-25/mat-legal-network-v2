<?php

namespace Database\Seeders;

use App\Models\Court;
use App\Models\Language;
use App\Models\PracticeArea;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('data/taxonomy.php');
        $i = 0;
        foreach ($data['practice_areas'] as $name => $description) {
            PracticeArea::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'description' => $description, 'sort_order' => $i++]);
        }
        foreach ($data['languages'] as $i => $name) {
            Language::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'sort_order' => $i]);
        }
        foreach ($data['courts'] as $i => $name) {
            Court::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'sort_order' => $i]);
        }
    }
}
