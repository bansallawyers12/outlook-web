<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/emails/send', [EmailController::class, 'send'])->name('emails.send');
});

require __DIR__.'/auth.php';

// OAuth routes
Route::get('/auth/{provider}', [AuthController::class, 'redirect'])->name('oauth.redirect');
Route::get('/auth/{provider}/callback', [AuthController::class, 'callback'])->name('oauth.callback');
