<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // لیست نقش‌هایی که برای آکادمی پردیس نیاز داریم
        $roles = [
            'Manager',    // مدیر کل (دسترسی به همه چیز + تنظیمات سیستم)
            'Admin',      // ادمین (مدیریت کاربران و محتوا)
            'Instructor', // استاد (مدیریت دوره‌های خودش)
            'Student',    // دانشجو (خرید و مشاهده دوره)
            'User',       // کاربر عادی (ثبت نام کرده ولی هنوز کاری نکرده)
        ];

        foreach ($roles as $roleName) {
            // firstOrCreate: یعنی اگر نیست بساز، اگر هست کاری نداشته باش (جلوگیری از تکرار)
            Role::firstOrCreate(['name' => $roleName]);
        }

        // --- تعریف مجوزها (Permissions) ---
        // اینجا می‌توانید دسترسی‌های ریز را تعریف کنید
        $permissions = [
            'create_course',
            'edit_course',
            'delete_course',
            'publish_course',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // --- اتصال نقش به مجوز (مثال) ---
        // به نقش Admin و Instructor اجازه ساخت دوره می‌دهیم
        $admin = Role::findByName('Admin');
        $instructor = Role::findByName('Instructor');
        $manager = Role::findByName('Manager');

        $manager->givePermissionTo($permissions);
        $admin->givePermissionTo('create_course', 'edit_course', 'delete_course', 'publish_course');
        $instructor->givePermissionTo('create_course', 'edit_course', 'delete_course', 'publish_course');
    }
}
