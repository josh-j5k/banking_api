<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisterUserController;
use App\Http\Controllers\BankAccountController;
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

Route::post('/register', [RegisterUserController::class, 'store']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/logout', [
        AuthenticatedSessionController::class, 'destroy'
    ]);
    Route::post('/create-account', [BankAccountController::class, 'store']);
    Route::patch('update-balance', [BankAccountController::class, 'updateBalance']);
    Route::post('/transfer', [BankAccountController::class, 'transfer']);
    Route::post('/retrieve-history', [BankAccountController::class, 'retrieveHistory']);
    Route::post('/retrieve-balance', [BankAccountController::class, 'retrieveBalance']);
});
