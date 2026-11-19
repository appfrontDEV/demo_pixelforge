<?php

use App\Http\Controllers\CostEstimateController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('cost-estimate', CostEstimateController::class)->name('cost-estimate');
});

require __DIR__.'/settings.php';
