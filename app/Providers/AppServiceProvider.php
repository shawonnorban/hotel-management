<?php

namespace App\Providers;

use App\Admin\Menu;
use App\Auth\UpgradingUserProvider;
use App\Models\Page;
use App\Models\User;
use App\Support\AppSettings;
use App\Support\Settings;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Older MySQL/MariaDB (e.g. WAMP bundles) limit index keys to 767/1000 bytes; 191 chars fits utf8mb4 everywhere.
        Schema::defaultStringLength(191);

        Paginator::useBootstrapFive();

        // Mail server chosen in the admin screen overrides .env (skipped before the table exists, e.g. during migrate).
        rescue(fn () => AppSettings::applyMailConfig(), report: false);

        Auth::provider('upgrading', fn ($app, array $config) => new UpgradingUserProvider($app['hash'], $config['model']));

        // The Super Admin role may do everything.
        Gate::before(fn ($user) => $user instanceof User && $user->hasRole('Super Admin') ? true : null);

        View::composer('*', function ($view) {
            $view->with('hotelName', rescue(fn () => Settings::hotelName(), config('app.name'), false));
        });

        View::composer('layouts.site', function ($view) {
            $pages = rescue(fn () => Page::published()->orderBy('sort')->orderBy('title')->get(), collect(), false);
            $view->with('sitePages', $pages->where('show_in_menu', true))->with('footerPages', $pages->where('show_in_footer', true));
        });

        View::composer('layouts.admin', function ($view) {
            $view->with('adminMenu', Menu::for(auth('admin')->user()));
        });
    }
}
