<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tickets')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

use App\Http\Controllers\TicketController;
Route::resource('tickets', TicketController::class)->middleware(['auth']);
