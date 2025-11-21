<?php

namespace App\Services\User;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Exception;
class AuthService
{
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
}
