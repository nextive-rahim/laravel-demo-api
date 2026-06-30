<?php

use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\CourseContentController;
use App\Http\Controllers\Api\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Api\Admin\ExamAnalyticsController;
use App\Http\Controllers\Api\Admin\ExamQuestionController;
use App\Http\Controllers\Api\Admin\QuestionController;
use App\Http\Controllers\Api\Admin\SubcategoryController;
use App\Http\Controllers\Api\Admin\UploadController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\AvatarController;
use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\ExamController;
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

// Authenticated student exam-taking (MCQ exams attached to course content).
Route::middleware('auth:sanctum')->group(function () {
    Route::get('courses/{course}/contents/{content}/exam', [ExamController::class, 'show']);
    Route::post('courses/{course}/contents/{content}/exam/start', [ExamController::class, 'start']);
    Route::post('courses/{course}/contents/{content}/exam/submit', [ExamController::class, 'submit']);
    Route::get('courses/{course}/contents/{content}/exam/result', [ExamController::class, 'result']);
    Route::get('courses/{course}/contents/{content}/exam/ranking', [ExamController::class, 'ranking']);
});

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

    // MCQ store: categories, subcategories and the question bank.
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('subcategories', SubcategoryController::class);
    Route::apiResource('questions', QuestionController::class);

    // Attach/detach store questions to an exam content item.
    Route::get('courses/{course}/contents/{content}/questions', [ExamQuestionController::class, 'index']);
    Route::post('courses/{course}/contents/{content}/questions', [ExamQuestionController::class, 'store']);
    Route::delete('courses/{course}/contents/{content}/questions/{question}', [ExamQuestionController::class, 'destroy']);

    // Exam analytics / analysis.
    Route::get('courses/{course}/contents/{content}/attempts', [ExamAnalyticsController::class, 'attempts']);
    Route::get('courses/{course}/contents/{content}/analysis', [ExamAnalyticsController::class, 'analysis']);

    // PDF upload to S3 (returns a URL for use in course content).
    Route::post('uploads/pdf', [UploadController::class, 'pdf']);
});
