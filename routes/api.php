<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlueprintController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::controller(BluePrintController::class)->prefix('/blueprints')->group(function() {
        Route::get('/', 'index')->name('blueprints.index');
        Route::post('/store', 'store')->name('blueprints.store');
        Route::get('/{blueprint}', 'show')->name('blueprints.show');
        Route::put('/{blueprint}/update', 'update')->name('blueprints.update');
        Route::delete('/{blueprint}/delete', 'destroy')->name('blueprints.destroy');
        
        // Route::delete('/{blueprint}/archive', 'archive')->name('blueprints.archive');
        // Route::post('/{blueprint}/restore', 'restore')->name('blueprints.restore');
        // Route::delete('/{blueprint}/forceDelete', 'forceDelete')->name('blueprints.forceDelete');
    });
});
