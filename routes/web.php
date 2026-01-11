<?php

use App\Http\Controllers\GameRoomController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('game.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [GameRoomController::class, 'index'])->name('dashboard');

    Route::get('/game', [GameRoomController::class, 'index'])->name('game.index');
    Route::get('/game/list', [GameRoomController::class, 'getRoomsList'])->name('game.list');
    Route::get('/game/create', [GameRoomController::class, 'create'])->name('game.create');
    Route::post('/game', [GameRoomController::class, 'store'])->name('game.store');
    Route::get('/game/{gameRoom}', [GameRoomController::class, 'show'])->name('game.show');
    Route::post('/game/{gameRoom}/join', [GameRoomController::class, 'join'])->name('game.join');
    Route::post('/game/{gameRoom}/start', [GameRoomController::class, 'start'])->name('game.start');
    Route::post('/game/{gameRoom}/submit', [GameRoomController::class, 'submitNumber'])->name('game.submit');
    Route::get('/game/{gameRoom}/status', [GameRoomController::class, 'getStatus'])->name('game.status');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
