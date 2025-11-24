<?php

// app/Services/Category/CategoryService.php

namespace App\Services\Category;

use App\Models\Admin\Category;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    /**
     * ایجاد دسته‌بندی جدید همراه با سئو
     */
    public function createCategory(array $data, User $creator): Category
    {
        return DB::transaction(function () use ($data, $creator) {
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
                'created_by' => $creator->id,
            ]);

            // 3. ذخیره اطلاعات سئو (با استفاده از همان Trait که قبلا ساختیم)
            if (isset($data['seo'])) {
                $category->updateSeo($data['seo']);
            }

            return $category;
        });
    }

    /**
     * ویرایش دسته‌بندی
     */
    public function updateCategory(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {

            // لاجیک آپدیت اسلاگ: اگر نام تغییر کرده بود، اسلاگ جدید بساز
            // نکته: اگر می‌خواهید اسلاگ ثابت بماند (برای سئو بهتر)، این شرط را بردارید
            if ($category->name !== $data['name']) {
                $slug = Str::slug($data['name']);
                if (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                    $slug .= '-' . rand(1000, 9999);
                }
                $data['slug'] = $slug;
            }

            // آپدیت اطلاعات پایه
            $category->update([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? $category->slug, // اگر اسلاگ جدید ساخته شد
                'parent_id' => $data['parent_id'],
                'is_active' => $data['is_active'] ?? $category->is_active,
            ]);

            // آپدیت اطلاعات سئو (اگر ارسال شده بود)
            if (isset($data['seo'])) {
                // متد updateSeo از Trait که قبلا ساختیم می‌آید
                $category->updateSeo($data['seo']);
            }

            return $category;
        });
    }


    /**
     * حذف پیشرفته دسته‌بندی با قابلیت انتقال محتوا
     * @param Category $category دسته‌بندی در حال حذف
     * @param int|null $migrateToId آی‌دی مقصد برای انتقال (اختیاری)
     * @throws \Exception
     */
    public function deleteCategory(Category $category, ?int $migrateToId = null): void
    {
        DB::transaction(function () use ($category, $migrateToId) {

            // سناریوی ۱: درخواست انتقال محتوا وجود دارد
            if ($migrateToId) {
                $targetCategory = Category::findOrFail($migrateToId);

                // چک کردن منطق درختی (جلوگیری از لوپ)
                // آیا مقصدی که انتخاب شده، یکی از زیرمجموعه‌های خودِ این دسته است؟
                // اگر بله، این کار غیرممکن است (پدربزرگ نمی‌تواند فرزندِ نوه‌ی خود شود)
                if ($this->isDescendant($targetCategory, $category->id)) {
                    throw new \Exception('نمی‌توانید محتوا را به یکی از زیرمجموعه‌های همین دسته منتقل کنید. ابتدا ساختار را اصلاح کنید.');
                }

                // الف) انتقال تمام دوره‌ها به دسته جدید
                $category->courses()->update(['category_id' => $migrateToId]);

                // ب) انتقال تمام زیرمجموعه‌ها به دسته جدید
                $category->children()->update(['parent_id' => $migrateToId]);

            } else {
                // سناریوی ۲: حذف ساده (بدون انتقال)
                // باید چک کنیم که دسته خالی باشد
                if ($category->children()->exists() || $category->courses()->exists()) {
                    throw new \Exception('این دسته خالی نیست! لطفاً شناسه مقصد (migrate_to_id) را ارسال کنید یا ابتدا محتوا را پاک کنید.');
                }
            }

            // حذف سئو
            $category->seo()->delete();

            // حذف نهایی
            $category->delete();
        });
    }

    /**
     * متد کمکی: بررسی میکند آیا یک دسته، فرزند (یا نوه) دسته دیگر است؟
     */
    private function isDescendant(Category $target, int $parentId): bool
    {
        // این یک پیاده‌سازی ساده است. برای درخت‌های خیلی عمیق می‌توان از روش‌های بهینه‌تر استفاده کرد
        $parent = $target->parent;
        while ($parent) {
            if ($parent->id === $parentId) {
                return true;
            }
            $parent = $parent->parent;
        }
        return false;
    }
}
