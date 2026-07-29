<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This application is an API-only backend. The administration UI lives in a
| separate React application and the storefront is a separate Next.js app.
| All functional endpoints are declared in routes/api.php.
|
| Only a minimal service descriptor is exposed here so that hitting the web
| root does not 404 for humans or uptime checks. Laravel's own health probe
| stays available at /up (see bootstrap/app.php).
|
*/

Route::get('/', fn () => response()->json([
    'service' => config('app.name'),
    'type' => 'api',
    'api' => url('/api/v1'),
    'health' => url('/api/v1/health'),
]));
