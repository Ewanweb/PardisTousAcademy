<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UpdateUserRoleRequest;
use App\Http\Requests\User\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\User\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        // انجام عملیات ثبت نام در سرویس
        $result = $this->authService->registerUser($request->validated());

        return response()->json([
            'message' => 'ثبت‌نام با موفقیت انجام شد.',
            'data' => [
                // نمایش اطلاعات کاربر با فرمت استاندارد Resource
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ],
        ], 201);
    }
    /**
     * ورود به سیستم
     */
    public function login(Request $request)
    {
        // 1. اعتبارسنجی ورودی
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 2. تلاش برای لاگین
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'اطلاعات ورود اشتباه است.'
            ], 401);
        }

        // 3. ساخت توکن
        $user = Auth::user();
        // چک کردن اینکه آیا کاربر فعال است؟
        if (!$user->is_active) {
            return response()->json(['message' => 'حساب کاربری شما غیرفعال است.'], 403);
        }

        // حذف توکن‌های قبلی (اختیاری - برای امنیت بیشتر)
        // $user->tokens()->delete();

        $token = $user->createToken('login_token')->plainTextToken;

        return response()->json([
            'message' => 'ورود موفقیت‌آمیز بود.',
            'data' => [
                'token' => $token,
                'user' => new UserResource($user),
            ]
        ]);
    }
    /**
     *ویرایش نقش
     */
    public function updateRoles(UpdateUserRoleRequest $request, User $user): JsonResponse
    {
        // جلوگیری از تغییر نقش خودِ مدیر توسط خودش (برای امنیت)
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'شما نمی‌توانید نقش خودتان را تغییر دهید.'], 403);
        }

        // انجام عملیات در سرویس
        $updatedUser = $this->authService->changeUserRoles($user, $request->validated()['roles']);

        return response()->json([
            'message' => 'نقش‌های کاربر با موفقیت تغییر کرد.',
            'data' => new UserResource($updatedUser), // لیست نقش‌های جدید در ریسورس برمی‌گردد
        ]);
    }
}
