<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Registration, self-service password reset, email verification, and password
// confirmation are intentionally not routed. This is an intranet system without a
// mail transport: an administrator creates every account and resets any forgotten
// password from the user management screen (system.users.edit). Users may still
// change their own password, which requires the current password, via
// personnel.profile.update.

Route::get('/', [HomeController::class, 'index'])->name('home');

// Authenticated Routes
Route::middleware(['auth', 'user.locale'])->group(function () {
    foreach (glob(__DIR__.'/modules/*.php') as $routeFile) {
        require $routeFile;
    }
});
