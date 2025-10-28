<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MediaController;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Admin media routes (for now open - protect with auth middleware in production)
|--------------------------------------------------------------------------
*/
Route::get('admin/media', [MediaController::class, 'index']);
Route::post('admin/media', [MediaController::class, 'store']);
Route::delete('admin/media/{media}', [MediaController::class, 'destroy']);
