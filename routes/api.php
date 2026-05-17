<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

// Public routes
Route::get('/health', function () {
    $status = ['status' => 'ok'];

    // Check database connection
    try {
        DB::connection()->getPdo();
        $status['database'] = 'ok';
    } catch (\Exception $e) {
        $status['database'] = 'error';
        $status['database_error'] = $e->getMessage();
    }

    // Check cache
    try {
        $testKey = 'health_check_' . time();
        Cache::put($testKey, true, 10);
        $cacheTest = Cache::get($testKey);
        $status['cache'] = ($cacheTest === true) ? 'ok' : 'error';
    } catch (\Exception $e) {
        $status['cache'] = 'error';
        $status['cache_error'] = $e->getMessage();
    }

    // Check session
    try {
        Session::put('health_check', true);
        $sessionTest = Session::get('health_check');
        $status['session'] = ($sessionTest === true) ? 'ok' : 'error';
    } catch (\Exception $e) {
        $status['session'] = 'error';
        $status['session_error'] = $e->getMessage();
    }

    $httpStatus = (
        $status['database'] === 'ok' &&
        $status['cache'] === 'ok' &&
        $status['session'] === 'ok'
    ) ? 200 : 500;

    return response()->json($status, $httpStatus);
});
