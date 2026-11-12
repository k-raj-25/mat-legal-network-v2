<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\ConsoleOutput;

/*
 * Helper for start.bat (local development only, never used in production).
 *
 *   php scripts/local/setup.php env            create/complete .env for a local SQLite run
 *   php scripts/local/setup.php needs-composer  exit 0 if `composer install` must run
 *   php scripts/local/setup.php composer-done   remember the composer.lock that was installed
 *   php scripts/local/setup.php app             migrate, seed, demo data, storage link
 *   php scripts/local/setup.php reset           wipe the local database and seed it again
 *   php scripts/local/setup.php free-port 8000  print the first free port from 8000 upwards
 *   php scripts/local/setup.php open 8000       wait for the server, then open the browser
 *
 * Every step is safe to run again: it only does work that is still missing.
 */

const LOCAL_ADMIN_EMAIL = 'admin@example.com';
const LOCAL_ADMIN_PASSWORD = 'Admin@12345';

$root = dirname(__DIR__, 2);
chdir($root);

$command = $argv[1] ?? '';
$arg = $argv[2] ?? null;

function say(string $line): void
{
    fwrite(STDOUT, $line.PHP_EOL);
}

function fail(string $line): never
{
    fwrite(STDERR, PHP_EOL.'ERROR: '.$line.PHP_EOL);
    exit(1);
}

/** Read a key from .env as plain text (no Laravel needed). */
function envValue(string $env, string $key): ?string
{
    if (! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $env, $m)) {
        return null;
    }

    return trim(trim($m[1]), '"\'');
}

function setEnvValue(string $env, string $key, string $value): string
{
    $line = $key.'='.$value;
    $pattern = '/^#?\s*'.preg_quote($key, '/').'=.*$/m';

    return preg_match($pattern, $env)
        ? preg_replace($pattern, str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $env, 1)
        : rtrim($env).PHP_EOL.$line.PHP_EOL;
}

function bootLaravel(string $root): void
{
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
}

function artisan(string $name, array $params = []): void
{
    $output = new ConsoleOutput;
    $code = Artisan::call($name, $params, $output);
    if ($code !== 0) {
        fail("`php artisan {$name}` failed (exit code {$code}). See the messages above.");
    }
}

function sqlitePath(): ?string
{
    if (config('database.default') !== 'sqlite') {
        return null;
    }
    $path = (string) config('database.connections.sqlite.database');

    return $path === ':memory:' ? null : $path;
}

function seedDemoIfEmpty(): void
{
    if (DB::table('lawyer_profiles')->exists()) {
        say('  Demo lawyers already present, skipping.');

        return;
    }
    say('  Adding demo lawyers (first run only)...');
    artisan('db:seed', ['--class' => 'Database\\Seeders\\DemoSeeder', '--force' => true]);
}

/** Older local databases got demo lawyers before the blog existed: add the demo editor and sample posts once. */
function seedBlogDemoIfMissing(): void
{
    if (DB::table('users')->where('email', 'editor@example.com')->exists()) {
        return;
    }
    say('  Adding the demo blog editor and sample posts...');
    artisan('db:seed', ['--class' => 'Database\\Seeders\\BlogDemoSeeder', '--force' => true]);
}

function printLogins(): void
{
    say('');
    say('  Admin login : '.(config('mat.admin.email') ?: '(set ADMIN_EMAIL in .env)'));
    say('  Password    : '.(config('mat.admin.password') ?: '(set ADMIN_PASSWORD in .env, then run start.bat again)'));
    say('  Demo lawyers: demo.lawyer2@example.com, demo.lawyer3@example.com, ... / password');
    say('  Blog editor : editor@example.com / Editor@12345 (can only manage blog posts)');
    say('  AI spam     : '.(filled(config('mat.spam_ai.api_key')) && config('mat.spam_ai.enabled')
        ? 'on ('.config('mat.spam_ai.model').')'
        : 'off (add ANTHROPIC_API_KEY to .env to turn it on, see README)'));
}

switch ($command) {
    case 'env':
        if (! file_exists('.env')) {
            if (! file_exists('.env.example')) {
                fail('.env.example is missing, so .env cannot be created.');
            }
            $env = file_get_contents('.env.example');
            // Local run: SQLite file database, no MySQL server needed.
            $env = setEnvValue($env, 'DB_CONNECTION', 'sqlite');
            foreach (['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $key) {
                $env = preg_replace('/^('.$key.'=.*)$/m', '# $1', $env);
            }
            // No background worker locally: send (log) e-mails straight away.
            $env = setEnvValue($env, 'QUEUE_CONNECTION', 'sync');
            $env = setEnvValue($env, 'APP_URL', 'http://127.0.0.1:8000');
            $env = setEnvValue($env, 'ADMIN_EMAIL', LOCAL_ADMIN_EMAIL);
            $env = setEnvValue($env, 'ADMIN_PASSWORD', LOCAL_ADMIN_PASSWORD);
            file_put_contents('.env', $env);
            say('  Created .env for a local SQLite setup.');
        } else {
            say('  Using existing .env.');
        }

        $env = file_get_contents('.env');
        if ((string) envValue($env, 'APP_KEY') === '') {
            $env = setEnvValue($env, 'APP_KEY', 'base64:'.base64_encode(random_bytes(32)));
            file_put_contents('.env', $env);
            say('  Generated APP_KEY.');
        }
        exit(0);

    case 'needs-composer':
        if (! file_exists('vendor/autoload.php')) {
            exit(0);
        }
        $stamp = @file_get_contents('vendor/.local-lock-hash');
        exit($stamp === md5_file('composer.lock') ? 1 : 0);

    case 'composer-done':
        file_put_contents('vendor/.local-lock-hash', md5_file('composer.lock'));
        exit(0);

    case 'app':
    case 'reset':
        bootLaravel($root);

        if ($path = sqlitePath()) {
            if (! is_dir(dirname($path))) {
                fail("The folder for the SQLite database does not exist: {$path}");
            }
            if (! file_exists($path)) {
                touch($path);
                say('  Created SQLite database: '.$path);
            }
        }

        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            fail('Cannot connect to the database ('.config('database.default')."):\n  ".$e->getMessage()
                ."\n  Check the DB_* settings in .env. For MySQL, make sure the server is running and the database exists.");
        }

        if ($command === 'reset') {
            say('  Wiping the local database...');
            artisan('migrate:fresh', ['--force' => true]);
        } else {
            artisan('migrate', ['--force' => true]);
        }
        artisan('db:seed', ['--force' => true]);   // reference data + admin (idempotent)
        seedDemoIfEmpty();
        seedBlogDemoIfMissing();

        if (! file_exists(public_path('storage'))) {
            artisan('storage:link');
        }
        printLogins();
        exit(0);

    case 'free-port':
        $port = max(1, (int) ($arg ?: 8000));
        for ($p = $port; $p < $port + 50; $p++) {
            $conn = @fsockopen('127.0.0.1', $p, $errno, $errstr, 0.3);
            if ($conn === false) {
                echo $p;
                exit(0);
            }
            fclose($conn);
        }
        fail("No free port found between {$port} and ".($port + 49).'.');

    case 'open':
        $port = (int) ($arg ?: 8000);
        $url = "http://127.0.0.1:{$port}";
        for ($i = 0; $i < 120; $i++) {   // up to ~60 seconds
            $conn = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.5);
            if ($conn !== false) {
                fclose($conn);
                break;
            }
            usleep(500_000);
        }
        if (PHP_OS_FAMILY === 'Windows') {
            pclose(popen('start "" "'.$url.'"', 'r'));
        }
        exit(0);

    default:
        fail("Unknown command '{$command}'.");
}
