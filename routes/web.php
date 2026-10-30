<?php

use App\Http\Controllers\GodController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/gods');
// Проверка уникальности имени прямо во время заполнения формы.
Route::get('gods/check-name', [GodController::class, 'checkName'])
    ->name('gods.check-name')
    ->middleware('throttle:60,1');
Route::resource('gods', GodController::class);
