<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['India', 'IN', '+91', 'INR', 'State Bar Council', true],
            ['United States', 'US', '+1', 'USD', 'State Bar', false],
            ['United Kingdom', 'GB', '+44', 'GBP', 'SRA / Bar Standards Board', false],
            ['United Arab Emirates', 'AE', '+971', 'AED', 'Ministry of Justice', false],
            ['Canada', 'CA', '+1', 'CAD', 'Law Society', false],
            ['Australia', 'AU', '+61', 'AUD', 'Law Society', false],
            ['Singapore', 'SG', '+65', 'SGD', 'Law Society of Singapore', false],
        ];
        foreach ($countries as [$name, $iso, $code, $cur, $bar, $active]) {
            Country::updateOrCreate(['iso2' => $iso], ['name' => $name, 'phone_code' => $code, 'currency' => $cur, 'bar_body_label' => $bar, 'is_active' => $active]);
        }

        $india = Country::where('iso2', 'IN')->first();
        foreach (require database_path('data/india.php') as $stateName => $cities) {
            $state = State::updateOrCreate(['country_id' => $india->id, 'slug' => Str::slug($stateName)], ['name' => $stateName]);
            foreach ($cities as $cityName) {
                City::updateOrCreate(['state_id' => $state->id, 'slug' => Str::slug($cityName)], ['name' => $cityName]);
            }
        }
    }
}
