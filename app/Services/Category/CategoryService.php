<?php

// app/Services/Category/CategoryService.php

namespace App\Services\Category;

use App\Models\Admin\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    /**
     * ایجاد دسته‌بندی جدید همراه با سئو
     */
    public function createCategory(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            // 1. ساخت اسلاگ یونیک از روی نام
            // نکته: در سیستم‌های خیلی بزرگ شاید نیاز به هندل کردن اسلاگ تکراری باشد
            $slug = Str::slug($data['name']);

            // اگر اسلاگ تکراری بود یک عدد رندوم تهش اضافه کن (ساده‌ترین روش)
            if (Category::where('slug', $slug)->exists()) {
                $slug .= '-' . rand(1000, 9999);
            }

            // 2. ایجاد رکورد دسته‌بندی
            $category = Category::create([
                'name' => $data['name'],
                'slug' => $slug,
                'parent_id' => $data['parent_id'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            // 3. ذخیره اطلاعات سئو (با استفاده از همان Trait که قبلا ساختیم)
            if (isset($data['seo'])) {
                $category->updateSeo($data['seo']);
            }

            return $category;
        });
    }
}
