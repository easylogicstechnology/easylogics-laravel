<?php

namespace App\Providers;

use App\Auth\CakePHPUserProvider;
use App\Hashing\LegacyCakeHasher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->app->make('hash')->extend('legacy-cake', function () {
            return new LegacyCakeHasher();
        });

        Auth::provider('cakephp', function ($app, array $config) {
            return new CakePHPUserProvider(
                new LegacyCakeHasher(),
                $config['model']
            );
        });
    }
}
