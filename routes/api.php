<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CabinetController;
use App\Http\Controllers\Api\CatchController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\FisherController;
use App\Http\Controllers\Api\FishHuntController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\LakeController;
use App\Http\Controllers\Api\LikeController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::get('/home/stats', [HomeController::class, 'stats']);
Route::get('/home/popular-lakes', [HomeController::class, 'popularLakes']);
Route::get('/home/recent-catches', [HomeController::class, 'recentCatches']);
Route::get('/posts', [HomeController::class, 'posts']);

Route::get('/lakes', [LakeController::class, 'index']);
Route::get('/lakes/{lake}', [LakeController::class, 'show']);

Route::get('/fishers/{user}', [FisherController::class, 'show']);
Route::get('/fishers/{user}/posts', [FisherController::class, 'posts']);
Route::get('/catches/{catchRecord}/comments', [CommentController::class, 'index']);

// `blocked`: an admin ban has to end the session that is already open,
// not just refuse the next login
Route::middleware(['auth:sanctum', 'blocked'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/user/profile', [AuthController::class, 'updateProfile']);
    Route::get('/cabinet', [CabinetController::class, 'show']);
    Route::get('/cabinet/achievements', [CabinetController::class, 'achievements']);
    Route::get('/cabinet/friends', [CabinetController::class, 'friends']);

    Route::get('/fish-hunt', [FishHuntController::class, 'progress']);
    Route::post('/fish-hunt/find', [FishHuntController::class, 'find']);

    // Finding people by name or @handle — auth-only, so handles are not scrapeable
    Route::get('/users/search', [FisherController::class, 'search']);

    Route::post('/fishers/{user}/follow', [FisherController::class, 'follow']);
    Route::delete('/fishers/{user}/follow', [FisherController::class, 'unfollow']);

    Route::post('/catches/{catchRecord}/like', [LikeController::class, 'toggle'])->middleware('throttle:likes');
    Route::post('/catches/{catchRecord}/comments', [CommentController::class, 'store'])->middleware('throttle:comments');
    Route::get('/catches', [CatchController::class, 'index']);
    Route::post('/catches', [CatchController::class, 'store'])->middleware('throttle:publications');
    Route::put('/catches/{catchRecord}', [CatchController::class, 'update']);
    Route::delete('/catches/{catchRecord}', [CatchController::class, 'destroy']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);
        Route::get('/chart', [AdminController::class, 'chart']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::post('/users/{user}/block', [AdminController::class, 'blockUser']);
        Route::post('/users/{user}/unblock', [AdminController::class, 'unblockUser']);
        Route::get('/catches', [AdminController::class, 'catches']);
        // Before the {catchRecord} route so "bulk" isn't captured as a model id
        Route::post('/catches/bulk-delete', [AdminController::class, 'deleteCatches']);
        Route::delete('/catches/{catchRecord}', [AdminController::class, 'deleteCatch']);
        // Before the {lake:id} routes so "heat" isn't captured as a model id
        Route::get('/lakes/heat', [AdminController::class, 'lakesHeat']);
        Route::get('/lakes', [AdminController::class, 'lakes']);
        Route::post('/lakes', [AdminController::class, 'storeLake']);
        Route::get('/lakes/{lake:id}', [AdminController::class, 'showLake']);
        Route::post('/lakes/{lake:id}', [AdminController::class, 'updateLake']);
        Route::delete('/lakes/{lake:id}', [AdminController::class, 'deleteLake']);
    });
});
