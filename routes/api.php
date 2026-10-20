<?php

use App\Http\Controllers\Api\ContractController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());

    Route::prefix('contracts')->name('contracts.')->group(function () {
        Route::post('/calculate', [ContractController::class, 'calculate'])->name('calculate');
    });
});
