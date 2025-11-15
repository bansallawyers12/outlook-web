<?php

use App\Http\Controllers\BrevoInboundController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api')->group(function () {
    Route::post('/brevo/inbound', BrevoInboundController::class)
        ->name('brevo.inbound');
});

