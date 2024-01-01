<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware('auth')->group(function () {
    Route::get('/', [AuthController::class, 'index']);
    Route::get('/', [TimeController::class, 'index']);
});

Route::post('/attendance/checkin', [TimeController::class, 'checkin']);
Route::post('/attendance/checkout', [TimeController::class, 'checkout']);
Route::post('/attendance/breakin', [TimeController::class, 'breakin']);
Route::post('/attendance/breakout', [TimeController::class, 'breakout']);