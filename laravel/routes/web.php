<?php

use App\Http\Controllers\PlatformController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| JobMarketSV application routes
|--------------------------------------------------------------------------
|
| The existing domain layer is mounted behind Laravel so the complete
| product remains available while modules are modernised independently.
| This catch-all deliberately supports every HTTP verb used by the web and
| JSON API routes of the application.
|
*/

Route::any('/{path?}', PlatformController::class)
    ->where('path', '.*')
    ->name('platform');
