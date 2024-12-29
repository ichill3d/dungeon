<?php

use Illuminate\Support\Facades\Route;
use App\Models\Dungeon;

Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/dungeons/create', function () {
    return view('dungeons.create');
})->name('dungeons.create');

Route::get('/dungeons/{id}/grid', function ($id) {
    $dungeon = Dungeon::findOrFail($id);
    $grid = json_decode($dungeon->grid, true);

    // Find the start ('S') tile
    $startX = null;
    $startY = null;

    foreach ($grid as $y => $row) {
        foreach ($row as $x => $cell) {
            if ($cell === 'S') {
                $startX = $x;
                $startY = $y;
                break 2; // Exit both loops
            }
        }
    }

    return view('dungeons.grid', [
        'dungeon' => $dungeon,
        'startX' => $startX,
        'startY' => $startY,
    ]);
})->name('dungeons.grid');

Route::get('/dungeons', function () {
    return view('dungeons');
})->name('dungeons.user');

Route::get('/settings', function () {
    return view('settings');
})->name('settings');
