<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // چک میکنیم اگر ایمیل وجود نداشت بسازدش
        $admin = User::firstOrCreate(
            ['email' => 'admin@pardis.com'], // شرط جستجو
            [
                'name' => 'مدیر ارشد',
                'password' => Hash::make('password123'), // رمز عبور
                'mobile' => '09120000000',
                'is_active' => true,
            ]
        );

        // مهم: دادن نقش ادمین به این کاربر
        $admin->assignRole('Manager');
    }
}
