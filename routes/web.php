<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DungeonController;


Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/dungeons/create', function () {
    return view('dungeons.create');
})->name('dungeons.create');

Route::get('/dungeons/show/{id}', [DungeonController::class, 'showDungeon'])->name('dungeons.show');
Route::get('/dungeons/test',  function () {
    return view('dungeons.test');
})->name('dungeons.test');


Route::get('/dungeons', function () {
    return view('dungeons');
})->name('dungeons.user');

Route::get('/settings', function () {
    return view('settings');
})->name('settings');
