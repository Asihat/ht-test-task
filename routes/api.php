<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
} );


Route::post('add', [\App\Http\Controllers\PaymentController::class, 'addMoneyToBalance']);
Route::post('sub', [\App\Http\Controllers\PaymentController::class, 'subMoneyFromBalance']);
Route::post('transfer', [\App\Http\Controllers\PaymentController::class, 'transferMoneyToUser']);
Route::get('balance/{id}', [\App\Http\Controllers\PaymentController::class, 'getBalance'])
    ->where('id', '[0-9]+');
