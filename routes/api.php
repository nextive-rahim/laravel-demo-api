<?php

use App\Http\Controllers\Api\Admin\CourseContentController;
use App\Http\Controllers\Api\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Api\Admin\UploadController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\AvatarController;
use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\CourseController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    // Public endpoints (the login -> password/signup flow).
    Route::post('check', [AuthController::class, 'check']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Forgot password (Redis-backed reset codes).
    Route::post('forgot-password', [PasswordController::class, 'forgot']);
    Route::post('reset-password', [PasswordController::class, 'reset']);

    // Authenticated endpoints.
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', [AuthController::class, 'user']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('change-password', [PasswordController::class, 'change']);

        // Avatar upload to S3.
        Route::post('avatar', [AvatarController::class, 'store']);
        Route::delete('avatar', [AvatarController::class, 'destroy']);
    });
});

// Public course catalog (website) — only published courses are exposed.
Route::get('courses', [CourseController::class, 'index']);
Route::get('courses/{course}', [CourseController::class, 'show']);
Route::get('courses/{course}/contents/{content}', [CourseController::class, 'content']);

// Admin-only endpoints (require an authenticated user with is_admin = true).
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('users', [UserController::class, 'index']);
    Route::get('stats', [UserController::class, 'stats']);

    // Course management + typed content items.
    Route::apiResource('courses', AdminCourseController::class);
    Route::get('courses/{course}/contents/{content}', [CourseContentController::class, 'show']);
    Route::post('courses/{course}/contents', [CourseContentController::class, 'store']);
    Route::put('courses/{course}/contents/{content}', [CourseContentController::class, 'update']);
    Route::patch('courses/{course}/contents/{content}', [CourseContentController::class, 'update']);
    Route::delete('courses/{course}/contents/{content}', [CourseContentController::class, 'destroy']);

    // PDF upload to S3 (returns a URL for use in course content).
    Route::post('uploads/pdf', [UploadController::class, 'pdf']);
});
