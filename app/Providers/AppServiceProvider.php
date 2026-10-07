<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
    }
}
