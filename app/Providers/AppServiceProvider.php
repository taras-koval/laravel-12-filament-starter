<?php

namespace App\Providers;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        Model::preventLazyLoading(!app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(!app()->isProduction());
        Model::preventAccessingMissingAttributes(!app()->isProduction());

        // DB::whenQueryingForLongerThan(500, function (Connection $connection, QueryExecuted $event) {
        //     // Notify development team...
        //     Log::warning("Database queries exceeded 500 ms on {$connection->getName()}");
        // });
    }
}
