<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        // Pagination links use Bootstrap 5 markup to match the rest of the UI.
        Paginator::useBootstrapFive();
    }
}
