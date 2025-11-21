<?php

namespace App\Services\User;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Exception;
class AuthService
{

    public function registerUser(array $data): array
    {
        return DB::transaction(function () use ($data) {

            // 1. ساخت کاربر
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'] ?? null,
                'password' => Hash::make($data['password']),
                'is_active' => true, // پیش‌فرض فعال است
            ]);

            // 2. اختصاص نقش پیش‌فرض (حتما باید سیدر RoleSeeder اجرا شده باشد)
            $user->assignRole('User');

            // 3. تولید توکن برای لاگین خودکار
            $token = $user->createToken('register_token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        });
    }
    public function registerStudent(array $data): array
    {
        return DB::transaction(function () use ($data) {

            // 1. ساخت کاربر
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'] ?? null,
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);

            // 2. اختصاص نقش (مهم‌ترین بخش برای جلوگیری از مشکل)
            // نکته: حتما باید سیدر (Seeder) نقش‌ها را اجرا کرده باشید
            $user->assignRole('Student');

            // 3. تولید توکن (برای اینکه کاربر بلافاصله لاگین شود)
            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        });
    }

    public function changeUserRoles(User $user, array $roles): User
    {
        // این متد جادویی Spatie است:
        // نقش‌های قبلی را می‌گیرد و نقش‌های جدید را جایگزین می‌کند
        $user->syncRoles($roles);

        return $user;
    }
}
