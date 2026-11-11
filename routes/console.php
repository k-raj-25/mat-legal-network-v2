<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Shared hosting has no long-running workers: cron runs `php artisan schedule:run` every minute,
// which drains the mail queue and exits.
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')->everyMinute()->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=168')->daily();

Artisan::command('mat:create-admin {email} {--name=MAT Admin}', function (string $email) {
    $password = $this->secret('Password (min 10 characters)');
    if (strlen((string) $password) < 10) {
        $this->error('Password must be at least 10 characters.');

        return 1;
    }
    $user = User::firstOrNew(['email' => strtolower($email)]);
    $user->forceFill(['name' => $this->option('name'), 'password' => $password, 'role' => Role::Admin, 'is_active' => true, 'email_verified_at' => now()])->save();
    $this->info('Admin account ready: '.$user->email);
})->purpose('Create or reset the admin account');
