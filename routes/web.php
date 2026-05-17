<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatWidgetController;
use App\Http\Controllers\ChatProxyController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SamlController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

$chatMiddleware = ['web', 'auth:sanctum', 'throttle:60,1'];
if (config('saml.enabled') === false) {
    $chatMiddleware = array_diff($chatMiddleware, ['auth:sanctum']);
}

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/login', [LoginController::class, 'login'])->name('login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware($chatMiddleware)->prefix('chat')->name('chat.')->group(function () {
    Route::get('/widget', [ChatWidgetController::class, 'index'])->name('widget');
    Route::post('/clear', [ChatWidgetController::class, 'clearHistory'])->name('clear');
    Route::post('/feedback', [ChatWidgetController::class, 'feedback'])->name('feedback');
    Route::post('/proxy', [ChatProxyController::class, 'proxy'])->name('proxy');
});

/*
|--------------------------------------------------------------------------
| SAML Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth/saml')->name('auth.saml.')->group(function () {
    Route::get('/login', [SamlController::class, 'login'])->name('login');
    Route::post('/acs', [SamlController::class, 'acs'])
        ->withoutMiddleware(VerifyCsrfToken::class)
        ->name('acs');
    Route::get('/logout', [SamlController::class, 'logout'])->name('logout');
    Route::get('/slo', [SamlController::class, 'slo'])->name('slo');
    Route::get('/metadata', [SamlController::class, 'metadata'])->name('metadata');
});
