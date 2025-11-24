<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * ایجاد کاربر جدید توسط مدیر
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // 1. ساخت کاربر
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'] ?? null,
                'password' => Hash::make($data['password']),
                'is_active' => $data['is_active'] ?? true,
            ]);

            // 2. تخصیص نقش‌ها (اگر ارسال شده باشد)
            // پیش‌فرض: اگر نقشی انتخاب نشد، Student بده
            $roles = $data['roles'] ?? ['Student'];
            $user->syncRoles($roles);

            return $user;
        });
    }

    /**
     * ویرایش کاربر
     */
    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            // 1. مدیریت رمز عبور (فقط اگر پر شده باشد آپدیت میشود)
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                // حذف کلید پسورد از آرایه تا نال نشود
                unset($data['password']);
            }

            // 2. آپدیت اطلاعات پایه
            // (نقش‌ها را از دیتا جدا میکنیم چون در متد update یوزر نیستند)
            $userData = Arr::except($data, ['roles', 'password_confirmation']);
            $user->update($userData);

            // 3. آپدیت نقش‌ها (اگر ارسال شده باشد)
            if (isset($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            return $user;
        });
    }

    /**
     * حذف کاربر
     */
    public function deleteUser(User $user): void
    {
        // جلوگیری از حذف خودِ مدیر
        if ($user->id === auth()->id()) {
            throw new \Exception('شما نمی‌توانید حساب خودتان را حذف کنید.');
        }

        $user->delete(); // Soft Delete

        // اگر خواستید نقش‌ها را هم بگیرید:
        // $user->syncRoles([]);
    }

    /**
     * تغییر نقش‌های کاربر (متد اختصاصی)
     */
    public function changeUserRoles(User $user, array $roles): User
    {
        $user->syncRoles($roles);
        return $user;
    }
}
