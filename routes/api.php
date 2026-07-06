<?php

use App\Http\Controllers\Api\AboutController;
use App\Http\Controllers\Api\Admin\AboutController as AdminAboutController;
use App\Http\Controllers\Api\Admin\AdvertisementController as AdminAdvertisementController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\CourseContentController;
use App\Http\Controllers\Api\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Api\Admin\ExamAnalyticsController;
use App\Http\Controllers\Api\Admin\ExamQuestionController;
use App\Http\Controllers\Api\Admin\FreeResourceController as AdminFreeResourceController;
use App\Http\Controllers\Api\Admin\LiveCourseController as AdminLiveCourseController;
use App\Http\Controllers\Api\Admin\NoticeController as AdminNoticeController;
use App\Http\Controllers\Api\Admin\PostController as AdminPostController;
use App\Http\Controllers\Api\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Api\Admin\QuestionController;
use App\Http\Controllers\Api\Admin\StudentReviewController as AdminStudentReviewController;
use App\Http\Controllers\Api\Admin\SubcategoryController;
use App\Http\Controllers\Api\Admin\UploadController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\AdvertisementController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\AvatarController;
use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\FreeResourceController;
use App\Http\Controllers\Api\LiveCourseController;
use App\Http\Controllers\Api\NoticeController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\StudentReviewController;
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

// Public notice board (website) — only published notices are exposed.
Route::get('notices', [NoticeController::class, 'index']);
Route::get('notices/{notice}', [NoticeController::class, 'show']);

// Public student reviews / success stories (website).
Route::get('student-reviews', [StudentReviewController::class, 'index']);
Route::get('student-reviews/{studentReview}', [StudentReviewController::class, 'show']);

// Public blog & news (website).
Route::get('posts', [PostController::class, 'index']);
Route::get('posts/{post}', [PostController::class, 'show']);

// Public "About us" content (website).
Route::get('about', [AboutController::class, 'show']);

// Public advertisements (website) — only live ads are exposed.
Route::get('advertisements', [AdvertisementController::class, 'index']);

// Public live courses (website).
Route::get('live-courses', [LiveCourseController::class, 'index']);
Route::get('live-courses/{liveCourse}', [LiveCourseController::class, 'show']);

// Public free resources (website).
Route::get('free-resources', [FreeResourceController::class, 'index']);

// Public academic / skill programs (website).
Route::get('programs', [ProgramController::class, 'index']);
Route::get('programs/{program}', [ProgramController::class, 'show']);

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

    // Notice board management (website + admin share the notices table).
    Route::apiResource('notices', AdminNoticeController::class);

    // Student reviews / success stories.
    Route::apiResource('student-reviews', AdminStudentReviewController::class);

    // Blog & news posts.
    Route::apiResource('posts', AdminPostController::class);

    // "About us" singleton content.
    Route::get('about', [AdminAboutController::class, 'show']);
    Route::put('about', [AdminAboutController::class, 'update']);

    // Advertisements (banners, popups, home promos).
    Route::apiResource('advertisements', AdminAdvertisementController::class);

    // Live courses, free resources and academic programs.
    Route::apiResource('live-courses', AdminLiveCourseController::class);
    Route::apiResource('free-resources', AdminFreeResourceController::class);
    Route::apiResource('programs', AdminProgramController::class);

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

    // PDF + image uploads to S3 (return a URL for use in content / notices).
    Route::post('uploads/pdf', [UploadController::class, 'pdf']);
    Route::post('uploads/image', [UploadController::class, 'image']);
});
