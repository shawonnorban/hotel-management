<?php

namespace App\Providers;

use App\Auth\LegacyUserProvider;
use App\Models\Legacy\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Hotel name comes from the legacy `setting` table (title column, row 2 in the CodeIgniter app).
        View::composer('*', function ($view) {
            static $name = null;
            $name ??= rescue(fn () => Setting::query()->where('id', 2)->value('title'), null, false) ?: config('app.name');
            $view->with('hotelName', $name);
        });

        Auth::provider('legacy', fn ($app, array $config) => new LegacyUserProvider($app['hash'], $config['model']));
    }
}
