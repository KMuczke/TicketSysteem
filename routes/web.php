<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tickets')->name('home');

Route::middleware(['auth', 'active'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;

Route::resource('tickets', TicketController::class)->middleware(['auth', 'active']);

Route::middleware(['auth', 'active'])->group(function () {
    Route::resource('users', UserController::class)->only(['index']);
});

Route::middleware(['auth', 'active', 'admin'])->group(function () {
    Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::resource('users', UserController::class)->except(['index', 'show', 'destroy']);
});
