<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

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
        // Prevent execution of destructive database commands (like drop or truncate) in production
        DB::prohibitDestructiveCommands(app()->isProduction());

        // Prevent lazy loading of relationships to avoid unexpected database queries (especially N+1 issues)
        // Disallow assigning attributes that are not listed in $fillable or $guarded
        // Prevent accessing attributes that are not loaded or do not exist on the model
        Model::shouldBeStrict(!app()->isProduction());

        // DB::whenQueryingForLongerThan(500, function (Connection $connection, QueryExecuted $event) {
        //     // Notify development team...
        //     Log::warning("Database queries exceeded 500 ms on {$connection->getName()}");
        // });

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
    }
}
