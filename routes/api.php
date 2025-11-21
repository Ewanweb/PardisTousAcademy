<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CourseController; // مطمئن شوید مسیر کنترلر درست است
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ========================================================================
    // 1. روت‌های عمومی (Public)
    // ========================================================================

    // احراز هویت
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // مشاهده دسته‌بندی‌ها
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::get('/categories/{category}/children', [CategoryController::class, 'children']);

    // مشاهده دوره‌های یک دسته (با استفاده از Slug برای زیبایی URL)
    Route::get('/categories/{category:slug}/courses', [CourseController::class, 'courseCategory']);

    // مشاهده دوره‌ها
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{course:slug}', [CourseController::class, 'show']);


    // ========================================================================
    // 2. روت‌های محافظت شده (Protected)
    // ========================================================================
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // --------------------------------------------------------------------
        // عملیات مدیریتی (Admin & Manager) - دسترسی کامل به همه‌چیز
        // --------------------------------------------------------------------
        Route::middleware(['role:Admin|Manager'])->group(function () {
            // مدیریت کامل دسته‌بندی‌ها
            Route::post('/categories', [CategoryController::class, 'store']);
            Route::put('/categories/{category}', [CategoryController::class, 'update']);
            Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

            // مدیریت کامل کاربران (اگر کنترلر User دارید فعال کنید)
            // Route::apiResource('users', UserController::class);
        });

        // --------------------------------------------------------------------
        // عملیات دوره (Admin & Manager & Instructor)
        // --------------------------------------------------------------------
        // نکته مهم: اینجا به همه این نقش‌ها اجازه ورود میدهیم.
        // محدودیت اینکه "استاد فقط دوره خودش را ببیند" در CoursePolicy کنترل می‌شود.
        Route::middleware(['role:Admin|Manager|Instructor'])->group(function () {

            Route::post('/courses', [CourseController::class, 'store']);
            Route::put('/courses/{course}', [CourseController::class, 'update']);
            Route::delete('/courses/{course}', [CourseController::class, 'destroy']);

            // آپلود ویدیو (آینده)
            // Route::post('/courses/{course}/upload', [CourseController::class, 'uploadVideo']);
        });

        // --------------------------------------------------------------------
        // عملیات دانشجویی
        // --------------------------------------------------------------------
        Route::middleware(['role:Student'])->group(function () {
            // Route::post('/courses/{course}/enroll', [EnrollmentController::class, 'store']);
        });

    });
});
