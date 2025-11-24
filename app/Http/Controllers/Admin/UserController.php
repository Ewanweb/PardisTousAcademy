<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected UserService $userService;

    // تزریق وابستگی سرویس یوزر
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * نمایش لیست کاربران (همراه با نقش‌ها و صفحه‌بندی)
     */
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $query = User::with('roles')->latest();

        // فیلتر بر اساس نقش (مثلاً ?role=Instructor)
        if ($request->has('role')) {
            $query->role($request->role);
        }

        // اگر درخواست 'all' بود، همه را برگردان (برای لیست‌های کشویی)
        if ($request->has('all')) {
            return UserResource::collection($query->get());
        }

        $users = $query->paginate(15);
        return UserResource::collection($users);
    }

    /**
     * نمایش اطلاعات یک کاربر خاص
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($user->load('roles')),
        ]);
    }

    /**
     * ایجاد کاربر جدید (توسط مدیر/ادمین)
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        // تمام منطق ساخت و تخصیص نقش در سرویس انجام می‌شود
        $user = $this->userService->createUser($request->validated());

        return response()->json([
            'message' => 'کاربر جدید با موفقیت ایجاد شد.',
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * ویرایش اطلاعات کاربر
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $updatedUser = $this->userService->updateUser($user, $request->validated());

        return response()->json([
            'message' => 'اطلاعات کاربر ویرایش شد.',
            'data' => new UserResource($updatedUser),
        ]);
    }

    /**
     * حذف کاربر
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            // سرویس چک می‌کند که کاربر خودش را حذف نکند
            $this->userService->deleteUser($user);

            return response()->json(['message' => 'کاربر با موفقیت حذف شد.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    /**
     * تغییر نقش‌های کاربر (متد اختصاصی)
     * این متد معمولاً برای Manager در دسترس است
     */
    public function updateRoles(Request $request, User $user): JsonResponse
    {
        // اعتبارسنجی دستی چون یک ریکوئست جداگانه نساختیم (می‌توانید بسازید)
        $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,name']
        ]);

        // جلوگیری از تغییر نقش خودِ مدیر توسط خودش (جهت امنیت)
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'نمی‌توانید نقش خودتان را تغییر دهید.'], 403);
        }

        $updatedUser = $this->userService->changeUserRoles($user, $request->roles);

        return response()->json([
            'message' => 'نقش‌های کاربر بروزرسانی شد.',
            'data' => new UserResource($updatedUser),
        ]);
    }
}
