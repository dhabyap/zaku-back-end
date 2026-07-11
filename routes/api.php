<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\RecurringTransactionController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes under /api/v1/*. Old /api/* paths redirect via RouteServiceProvider.
|
*/

// --- v1 routes ---
Route::prefix('v1')->group(function () {

    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'app' => config('app.name'),
            'version' => '1.0.0',
        ]);
    });

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:registration');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
        Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
        Route::post('/resend-verification', [AuthController::class, 'resendVerification'])->middleware('throttle:verification');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset');
        Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware('jwt.auth');
        Route::get('/me', [AuthController::class, 'me'])->middleware('jwt.auth');
        Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('jwt.auth');
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('jwt.auth');
    });

    Route::middleware('jwt.auth')->group(function () {
        Route::get('/user/profile', [UserController::class, 'profile']);
        Route::put('/user/profile', [UserController::class, 'updateProfile']);
        Route::put('/user/budget', [UserController::class, 'updateBudget']);

        Route::get('/dashboard', [DashboardController::class, 'index']);

        Route::get('/transactions', [TransactionController::class, 'index']);
        Route::get('/insights', [\App\Http\Controllers\Api\InsightController::class, 'index']);
        Route::post('/transactions', [TransactionController::class, 'store']);
        Route::put('/transactions/{id}', [TransactionController::class, 'update']);
        Route::get('/transactions/stats', [TransactionController::class, 'stats']);
        Route::get('/transactions/categories', [TransactionController::class, 'categories']);
        Route::post('/ai/chat', [TransactionController::class, 'aiChat']);
        Route::get('/transactions/{id}', [TransactionController::class, 'show']);
        Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);
        Route::post('/transactions/chat', [TransactionController::class, 'chat']);

        Route::get('/recurring-transactions', [RecurringTransactionController::class, 'index']);
        Route::post('/recurring-transactions', [RecurringTransactionController::class, 'store']);
        Route::get('/recurring-transactions/{id}', [RecurringTransactionController::class, 'show']);
        Route::put('/recurring-transactions/{id}', [RecurringTransactionController::class, 'update']);
        Route::delete('/recurring-transactions/{id}', [RecurringTransactionController::class, 'destroy']);

        Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);

        Route::get('/changelogs', [\App\Http\Controllers\Api\ChangelogController::class, 'index']);
    });

});

// --- Legacy /api/* → /api/v1/* redirect (only for non-v1 paths) ---
Route::any('/{path}', function (string $path) {
    if (str_starts_with($path, 'v1/')) {
        return response()->json(['message' => 'Not Found.'], 404);
    }
    return redirect()->to('/api/v1/'.$path);
})->where('path', '.*');
