<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketNoteController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tickets')->name('home');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/settings.php';

Route::middleware(['auth', 'active'])->group(function () {
    Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::patch('tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');
    Route::delete('tickets/{ticket}/release', [TicketController::class, 'release'])->name('tickets.release');
    Route::patch('tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status');
    Route::patch('tickets/{ticket}/classification', [TicketController::class, 'updateClassification'])->name('tickets.classification');

    Route::scopeBindings()->group(function () {
        Route::post('tickets/{ticket}/comments', [TicketCommentController::class, 'store'])
            ->name('tickets.comments.store');
        Route::get('tickets/{ticket}/comments/{comment}/edit', [TicketCommentController::class, 'edit'])
            ->name('tickets.comments.edit');
        Route::put('tickets/{ticket}/comments/{comment}', [TicketCommentController::class, 'update'])
            ->name('tickets.comments.update');
        Route::delete('tickets/{ticket}/comments/{comment}', [TicketCommentController::class, 'destroy'])
            ->name('tickets.comments.destroy');

        Route::post('tickets/{ticket}/notes', [TicketNoteController::class, 'store'])
            ->name('tickets.notes.store');
        Route::delete('tickets/{ticket}/notes/{note}', [TicketNoteController::class, 'destroy'])
            ->name('tickets.notes.destroy');
    });

    Route::resource('users', UserController::class)->only(['index']);
});

Route::middleware(['auth', 'active', 'admin'])->group(function () {
    Route::resource('tickets', TicketController::class)->only(['destroy']);

    Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::resource('users', UserController::class)->except(['index', 'show', 'destroy']);

    Route::patch('categories/{category}/toggle-active', [CategoryController::class, 'toggleActive'])->name('categories.toggle-active');
    Route::resource('categories', CategoryController::class)->except(['show', 'destroy']);

    Route::patch('applications/{application}/toggle-active', [ApplicationController::class, 'toggleActive'])->name('applications.toggle-active');
    Route::resource('applications', ApplicationController::class)->except(['show', 'destroy']);
});
