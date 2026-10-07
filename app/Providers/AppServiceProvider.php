<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (!in_array(request()->getHost(), ['127.0.0.1', 'localhost'])) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Paginator::defaultView('vendor.pagination.portal');
        Paginator::defaultSimpleView('vendor.pagination.simple-default');
    }
}
