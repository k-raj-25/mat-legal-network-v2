<?php

namespace App\Providers;

use Anthropic\Client;
use App\Models\Setting;
use App\Services\Spam\AnthropicSpamClassifier;
use App\Services\Spam\SpamClassifier;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SpamClassifier::class, fn () => new AnthropicSpamClassifier(
            new Client(
                apiKey: (string) config('mat.spam_ai.api_key'),
                baseUrl: config('mat.spam_ai.base_url') ?: null,
                openTelemetry: false,
            ),
            (string) config('mat.spam_ai.model'),
            (float) config('mat.spam_ai.timeout'),
        ));
    }

    public function boot(): void
    {
        Paginator::defaultView('pagination.mat');

        if ($this->app->isProduction()) {
            URL::forceHttps();
        }

        // Company contact details & legal settings available in every view.
        View::composer('*', function ($view) {
            static $settings;
            $settings ??= rescue(fn () => Schema::hasTable('settings') ? Setting::allValues() : config('mat.settings_defaults'), config('mat.settings_defaults'), false);
            $view->with('site', $settings);
        });

        // Abuse / scraping protection.
        RateLimiter::for('search', fn (Request $r) => Limit::perMinute(config('mat.search_rate_limit'))->by($r->ip()));
        RateLimiter::for('enquiries', fn (Request $r) => [
            Limit::perHour(5)->by('enq-h:'.$r->ip()),
            Limit::perDay(20)->by('enq-d:'.$r->ip()),
        ]);
        RateLimiter::for('login', fn (Request $r) => [
            Limit::perMinute(5)->by(strtolower((string) $r->input('email')).'|'.$r->ip()),
            Limit::perMinute(20)->by($r->ip()),
        ]);
        RateLimiter::for('register', fn (Request $r) => Limit::perHour(5)->by($r->ip()));
        RateLimiter::for('password-reset', fn (Request $r) => Limit::perHour(5)->by($r->ip()));
    }
}
