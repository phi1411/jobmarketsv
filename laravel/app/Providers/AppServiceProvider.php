<?php

namespace App\Providers;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use LogicException;

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
        if (! config('database.legacy_read_only', true)) {
            return;
        }

        DB::connection()->beforeExecuting(function (string $query, array $bindings, Connection $connection): void {
            if (! preg_match('/^\s*(select|show|describe|explain|with)\b/i', $query)) {
                throw new LogicException('Legacy database is configured as read-only during migration.');
            }
        });
    }
}
