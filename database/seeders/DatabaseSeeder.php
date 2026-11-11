<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Production-safe seed: reference data + the admin account (if configured in .env). */
    public function run(): void
    {
        $this->call([LocationSeeder::class, TaxonomySeeder::class]);

        if (config('mat.admin.email') && config('mat.admin.password')) {
            $admin = User::firstOrNew(['email' => config('mat.admin.email')]);
            if (! $admin->exists) {
                $admin->forceFill([
                    'name' => config('mat.admin.name'),
                    'password' => config('mat.admin.password'),
                    'role' => Role::Admin,
                    'email_verified_at' => now(),
                ])->save();
            }
        }
    }
}
