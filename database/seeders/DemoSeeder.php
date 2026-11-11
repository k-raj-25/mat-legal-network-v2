<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\City;
use App\Models\Country;
use App\Models\Court;
use App\Models\Language;
use App\Models\PracticeArea;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo / load-test data. NEVER run in production.
 *   php artisan db:seed --class=DemoSeeder            (60 lawyers)
 *   DEMO_LAWYERS=50000 php artisan db:seed --class=DemoSeeder
 * Uses bulk inserts so 50k rows seed in well under a minute.
 */
class DemoSeeder extends Seeder
{
    private const FIRST = ['Aarav', 'Vivaan', 'Aditya', 'Arjun', 'Rohan', 'Rahul', 'Karan', 'Vikram', 'Sanjay', 'Amit', 'Rajesh', 'Suresh', 'Anil', 'Manoj', 'Deepak', 'Nikhil', 'Harsh', 'Kunal', 'Imran', 'Faisal', 'Gurpreet', 'Harpreet', 'Joseph', 'Thomas', 'Priya', 'Ananya', 'Diya', 'Kavya', 'Isha', 'Neha', 'Pooja', 'Sneha', 'Ritu', 'Meera', 'Lakshmi', 'Divya', 'Shreya', 'Sana', 'Ayesha', 'Fatima', 'Simran', 'Anjali', 'Nandini', 'Swati', 'Rekha', 'Sunita'];

    private const LAST = ['Sharma', 'Verma', 'Gupta', 'Singh', 'Kumar', 'Patel', 'Shah', 'Mehta', 'Joshi', 'Iyer', 'Nair', 'Menon', 'Reddy', 'Rao', 'Naidu', 'Pillai', 'Das', 'Bose', 'Chatterjee', 'Banerjee', 'Mukherjee', 'Khan', 'Ahmed', 'Qureshi', 'Sheikh', 'Gill', 'Sandhu', 'Malhotra', 'Kapoor', 'Chopra', 'Agarwal', 'Jain', 'Desai', 'Kulkarni', 'Patil', 'Deshmukh', 'Mishra', 'Tiwari', 'Pandey', 'Yadav', 'Thakur', 'Fernandes', "D'Souza", 'Mathew'];

    public function run(): void
    {
        $count = (int) env('DEMO_LAWYERS', 60);
        $country = Country::where('iso2', 'IN')->value('id');
        $cities = City::get(['id', 'state_id'])->all();
        $areas = PracticeArea::pluck('id')->all();
        $langs = Language::pluck('id')->all();
        $english = Language::where('slug', 'english')->value('id');
        $hindi = Language::where('slug', 'hindi')->value('id');
        $courts = Court::pluck('id')->all();
        $password = Hash::make('password');
        $now = now();
        $startUserId = (int) DB::table('users')->max('id');

        foreach (array_chunk(range(1, $count), 1000) as $chunk) {
            $users = $profiles = [];
            foreach ($chunk as $n) {
                $name = self::FIRST[array_rand(self::FIRST)].' '.self::LAST[array_rand(self::LAST)];
                $users[] = ['name' => $name, 'email' => 'demo.lawyer'.($startUserId + $n).'@example.com', 'password' => $password, 'role' => Role::Lawyer->value, 'is_active' => 1, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now];
            }
            DB::table('users')->insert($users);
            $userIds = DB::table('users')->whereIn('email', array_column($users, 'email'))->pluck('id', 'email');

            foreach ($users as $i => $u) {
                $city = $cities[array_rand($cities)];
                $code = 'MLN-'.strtoupper(Str::random(7));
                $roll = mt_rand(1, 100);
                $status = $roll <= 85 ? 'approved' : ($roll <= 95 ? 'pending' : 'rejected');
                $profiles[] = [
                    'user_id' => $userIds[$u['email']], 'public_code' => $code,
                    'slug' => Str::slug('adv '.$u['name']).'-'.Str::lower(substr($code, 4)),
                    'full_name' => 'Adv. '.$u['name'], 'gender' => mt_rand(0, 1) ? 'male' : 'female',
                    'designation' => mt_rand(1, 20) === 1 ? 'Senior Advocate' : 'Advocate',
                    'firm_name' => mt_rand(0, 2) ? null : self::LAST[array_rand(self::LAST)].' & Associates',
                    'practice_since' => mt_rand(1980, (int) $now->year), 'education' => mt_rand(0, 1) ? 'LL.B.' : 'B.A. LL.B. (Hons.), LL.M.',
                    'bio' => 'Practising advocate handling matters before trial courts and tribunals, with a focus on clear advice and timely updates for clients.',
                    'consultation_fee' => [null, 500, 1000, 1500, 2000, 3000][mt_rand(0, 5)],
                    'offers_online_consultation' => mt_rand(0, 1),
                    'country_id' => $country, 'state_id' => $city->state_id, 'city_id' => $city->id,
                    'phone' => '9'.str_pad((string) mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT),
                    'enrolment_number' => 'D/'.mt_rand(100, 99999).'/'.mt_rand(1980, 2026),
                    'bar_council' => 'Bar Council of Delhi', 'status' => $status,
                    'status_reason' => $status === 'rejected' ? 'Enrolment certificate is not readable. Please upload a clearer copy.' : null,
                    'submitted_at' => $now, 'approved_at' => $status === 'approved' ? $now->copy()->subDays(mt_rand(0, 365)) : null,
                    'is_featured' => mt_rand(1, 50) === 1, 'created_at' => $now, 'updated_at' => $now,
                ];
            }
            DB::table('lawyer_profiles')->insert($profiles);
            $ids = DB::table('lawyer_profiles')->whereIn('user_id', $userIds->values())->pluck('id');

            $pa = $pl = $pc = [];
            foreach ($ids as $id) {
                foreach ((array) array_rand(array_flip($areas), mt_rand(1, 4)) as $a) {
                    $pa[] = ['lawyer_profile_id' => $id, 'practice_area_id' => $a];
                }
                foreach (array_unique([$english, $hindi, $langs[array_rand($langs)]]) as $l) {
                    if (mt_rand(0, 3) || $l === $english) {
                        $pl[] = ['lawyer_profile_id' => $id, 'language_id' => $l];
                    }
                }
                foreach ((array) array_rand(array_flip($courts), mt_rand(1, 3)) as $c) {
                    $pc[] = ['lawyer_profile_id' => $id, 'court_id' => $c];
                }
            }
            foreach (array_chunk($pa, 2000) as $c) {
                DB::table('lawyer_practice_area')->insert($c);
            }
            foreach (array_chunk($pl, 2000) as $c) {
                DB::table('language_lawyer')->insert($c);
            }
            foreach (array_chunk($pc, 2000) as $c) {
                DB::table('court_lawyer')->insert($c);
            }
        }
    }
}
