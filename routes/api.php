<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\FuelRecordController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\DashboardController;

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
});

// Dashboard routes
Route::prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/monthly-report/{year}/{month}', [DashboardController::class, 'monthlyReport']);
    Route::get('/yearly-report/{year}', [DashboardController::class, 'yearlyReport']);
});

// Expense routes
Route::apiResource('expenses', ExpenseController::class);
Route::get('expenses/category/{category}', [ExpenseController::class, 'byCategory']);
Route::get('expenses/monthly/{year}/{month}', [ExpenseController::class, 'monthly']);

// Fuel Record routes
Route::apiResource('fuel-records', FuelRecordController::class);
Route::get('fuel-records/efficiency', [FuelRecordController::class, 'efficiency']);
Route::get('fuel-records/monthly/{year}/{month}', [FuelRecordController::class, 'monthly']);

// Reminder routes
Route::apiResource('reminders', ReminderController::class);
Route::get('reminders/pending', [ReminderController::class, 'pending']);
Route::get('reminders/upcoming/{days}', [ReminderController::class, 'upcoming']);
Route::put('reminders/{id}/complete', [ReminderController::class, 'markAsCompleted']);
