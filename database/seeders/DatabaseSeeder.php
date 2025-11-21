<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // لیست سیدرهایی که میخواهید اجرا شوند را اینجا به ترتیب بنویسید
        $this->call([

            // 1. اول باید نقش‌ها ساخته شوند
            RoleSeeder::class,

            // 2. بعد ادمین ساخته شود (چون ادمین نیاز به نقش دارد)
            AdminSeeder::class,

            // 3. (اختیاری) سایر سیدرها مثل دسته‌بندی‌های اولیه
            // CategorySeeder::class,
        ]);
    }
}
