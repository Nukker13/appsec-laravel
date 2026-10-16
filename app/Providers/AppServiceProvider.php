<?php

namespace App\Providers;

use App\Contracts\LabRunner;
use App\Models\Lab;
use App\Models\User;
use App\Services\DockerLabRunner;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LabRunner::class, DockerLabRunner::class);
    }

    public function boot(): void
    {
        Gate::define('admin', fn (User $user): bool => $user->isAdmin());

        // 10 flag submissions per minute, keyed user_id:lab_id.
        RateLimiter::for('flag-check', function (Request $request): Limit {
            // Throttle can run before route-model binding, so "lab" may still be a raw slug.
            $lab = $request->route('lab');
            $labId = $lab instanceof Lab ? $lab->getKey() : Lab::query()->where('slug', (string) $lab)->value('id');

            return Limit::perMinute(10)->by($request->user()?->getAuthIdentifier().':'.$labId);
        });
    }
}
