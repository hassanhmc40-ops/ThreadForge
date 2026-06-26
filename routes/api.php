<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlueprintController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::controller(BlueprintController::class)->prefix('/blueprints')->group(function() {
        Route::get('/', 'index')->name('blueprints.index');
        Route::post('/', 'store')->name('blueprints.store');
        Route::get('/{blueprint}', 'show')->name('blueprints.show');
        Route::put('/{blueprint}', 'update')->name('blueprints.update');
        Route::delete('/{blueprint}', 'destroy')->name('blueprints.destroy');
    });

    Route::controller(ContentController::class)->prefix('/content')->group(function () {
        Route::get('/', 'index')->name('content.index');
        Route::post('/repurpose', 'repurpose')->name('content.repurpose');
        Route::get('/{rawContent}', 'show')->name('content.show');
    });

    Route::controller(PostController::class)->prefix('/posts')->group(function () {
        Route::get('/', 'index')->name('posts.index');
        Route::get('/{id}', 'show')->name('posts.show');
        Route::patch('/{id}/status', 'updateStatus')->name('posts.status');
    });

    Route::controller(ChatController::class)->prefix('/posts/{id}/chat')->group(function () {
        Route::post('/', 'chat')->name('chat.chat');
        Route::get('/', 'history')->name('chat.history');
    });
});
