<?php

use App\Http\Controllers\Sonel\Client\ClientProfileController;
use App\Http\Controllers\Sonel\Client\PurchaseController;
use App\Http\Controllers\Sonel\Client\PurchaseHistoryController;
use App\Http\Controllers\Sonel\Client\RegisterController;
use Hacp0012\Quest\Quest;
use Illuminate\Support\Facades\Route;


Route::prefix('client')->group(function() {
    Quest::spawn('profile', ClientProfileController::class);
    Quest::spawn('register', RegisterController::class);
    Quest::spawn('purchase', [
        PurchaseController::class,
        PurchaseHistoryController::class,
    ]);
});

Route::prefix('admin')->group(function() {
    //
});