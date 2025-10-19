<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SuggestionController;
use Illuminate\Support\Facades\Route;

Route::post('/register',[AuthController::class,'register']);
Route::post('/login',[AuthController::class,'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me',[AuthController::class,'me']);
    Route::post('/logout',[AuthController::class,'logout']);

   Route::apiResource('categories', CategoryController::class)->except(['show']);

    Route::apiResource('transactions', TransactionController::class);
  
    Route::patch('/transactions/{id}', [TransactionController::class, 'quickUpdate']);


    // Dashboard snapshot for a month (income, expense, remaining, current balance)
    Route::get('/dashboard', [ReportController::class,'dashboard']); // ?month=2025-09

    // Yearly totals (earning/spend per month)
    Route::get('/reports/year/{year}', [ReportController::class,'year']);

    // Suggestions: where to reduce costs
    Route::get('/suggestions', [SuggestionController::class,'index']); // ?month=2025-09
});
