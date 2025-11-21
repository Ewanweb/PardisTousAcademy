<?php

namespace App\Services\Course;

use App\Models\Admin\Course;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CourseService
{
    /**
     * دوره جدید میسازد و عملیات جانبی را مدیریت میکند.
     */
    public function createCourse(array $data, User $instructor): Course
    {
        return DB::transaction(function () use ($data, $instructor) {

            // ساخت اسلاگ (چون تایتل یکتاست، اسلاگ هم احتمالا یکتاست، اما محض اطمینان یونیک میکنیم)
            $slug = Str::slug($data['title']);
            if (Course::where('slug', $slug)->exists()) {
                $slug .= '-' . time();
            }

            // یکسان‌سازی نام فیلد عکس
            $thumbnail = $data['image'] ?? $data['thumbnail'] ?? null;

            $course = Course::create([
                'instructor_id' => $instructor->id,
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'slug' => $slug,
                'description' => $data['description'],
                'price' => $data['price'],
                'status' => $data['status'] ?? 'draft',
                'thumbnail' => $thumbnail,
            ]);

            // ✅ ذخیره سئو (بلافاصله بعد از ساخت دوره)
            if (isset($data['seo'])) {
                $course->updateSeo($data['seo']);
            }

            return $course;
        });
    }
    public function updateCourse(Course $course, array $data): Course
    {
        return DB::transaction(function () use ($course, $data) {

            // 1. اگر عنوان (Title) تغییر کرده بود، باید اسلاگ هم آپدیت شود
            if (isset($data['title']) && $data['title'] !== $course->title) {
                // ساخت اسلاگ جدید
                $slug = Str::slug($data['title']);

                // اگر اسلاگ تکراری بود (برای محکم کاری، هرچند تایتل را چک کردیم)
                if (Course::where('slug', $slug)->where('id', '!=', $course->id)->exists()) {
                    $slug .= '-' . time();
                }

                $data['slug'] = $slug;
            }

            // 2. هندل کردن عکس (اگر ارسال شده بود)
            // اگر در ورودی 'image' می‌آید ولی در دیتابیس 'thumbnail' است:
            if (isset($data['image'])) {
                $data['image'] = $data['image'];
                unset($data['image']); // پاک کردن کلید اضافه
            }

            // 3. آپدیت اطلاعات اصلی دوره
            // ما seo را از آرایه $data جدا نمیکنیم چون مدل Course هوشمند است و فیلدهای اضافه را نادیده می‌گیرد
            // اما برای تمیزی بهتر است seo را جدا کنیم
            $course->update(\Illuminate\Support\Arr::except($data, ['seo']));

            if (isset($data['seo'])) {
                // این متد updateSeo از همان Trait HasSeo می‌آید که قبلاً ساختیم
                $course->updateSeo($data['seo']);
            }

            return $course;
        });
    }

    public function deleteCourse(Course $course): void
    {
        // حذف سئو و سپس خود دوره (Soft Delete)
        $course->seo()->delete();
        $course->delete();
    }
}
