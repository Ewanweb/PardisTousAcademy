<?php

namespace App\Services\Course;

use App\Models\Admin\Category; // مطمئن شوید مسیر مدل درست است
use App\Models\Admin\Course;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CourseService
{
    /**
     * تعیین مدرس نهایی بر اساس دسترسی
     */
    private function determineInstructor(User $currentUser, ?int $requestedInstructorId): int
    {
        // اگر کاربر ادمین یا منیجر است و درخواست تغییر مدرس داده، آی‌دی درخواستی را قبول کن
        if ($currentUser->hasAnyRole(['Admin', 'Manager']) && $requestedInstructorId) {
            return $requestedInstructorId;
        }

        // در غیر این صورت (مدرس معمولی یا عدم ارسال آی‌دی)، خودِ کاربر جاری مدرس است
        return $currentUser->id;
    }

    /**
     * ایجاد دوره جدید
     */
    public function createCourse(array $data, User $currentUser): Course
    {
        return DB::transaction(function () use ($data, $currentUser) {

            // 1. آپلود تصویر
            $thumbnailPath = null;
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                $file = $data['image'];
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('uploads', $filename, 'public');
                $thumbnailPath = url('storage/' . $path);
            }

            // 2. تعیین مدرس
            $instructorId = $this->determineInstructor($currentUser, $data['instructor_id'] ?? null);

            // 3. ساخت رکورد دوره
            $course = Course::create([
                'instructor_id' => $instructorId,
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'slug' => Str::slug($data['title']) . '-' . time(),
                'description' => $data['description'],
                'price' => $data['price'],
                'status' => $data['status'] ?? 'draft',
                'thumbnail' => $thumbnailPath,
            ]);

            // 4. افزایش شمارنده دسته‌بندی
            if ($course->category_id) {
                Category::where('id', $course->category_id)->increment('courses_count');
            }

            // 5. ذخیره سئو
            if (isset($data['seo'])) {
                $seoData = is_string($data['seo']) ? json_decode($data['seo'], true) : $data['seo'];
                $course->updateSeo($seoData);
            }

            return $course;
        });
    }

    /**
     * ویرایش دوره
     */
    public function updateCourse(Course $course, array $data): Course
    {
        return DB::transaction(function () use ($course, $data) {
            $oldCategoryId = $course->category_id;

            // 1. آپدیت اسلاگ (اگر عنوان تغییر کرد)
            if (isset($data['title']) && $data['title'] !== $course->title) {
                $slug = Str::slug($data['title']);
                if (Course::where('slug', $slug)->where('id', '!=', $course->id)->exists()) {
                    $slug .= '-' . time();
                }
                $data['slug'] = $slug;
            }

            // 2. مدیریت تصویر (حذف قبلی و آپلود جدید)
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                if ($course->thumbnail) {
                    $oldPath = str_replace(url('storage/'), '', $course->thumbnail);
                    Storage::disk('public')->delete($oldPath);
                }
                $file = $data['image'];
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('uploads', $filename, 'public');
                $data['thumbnail'] = url('storage/' . $path);
            } else {
                unset($data['image']); // اگر عکس جدیدی نیست، مقدار قبلی حفظ شود
            }

            // پاک کردن فیلدهای اضافی فرانت‌‌اند
            unset($data['thumbnail']);

            // 3. آپدیت سئو
            if (isset($data['seo'])) {
                $seoData = is_string($data['seo']) ? json_decode($data['seo'], true) : $data['seo'];
                $course->updateSeo($seoData);
                unset($data['seo']);
            }

            // 4. تغییر مدرس (فقط برای ادمین/منیجر)
            // نکته: اگر می‌خواهید در ویرایش هم بتوانند مدرس را عوض کنند، این بخش را فعال کنید
            if (isset($data['instructor_id']) && auth()->user()->hasAnyRole(['Admin', 'Manager'])) {
                // اجازه تغییر دارد
            } else {
                unset($data['instructor_id']); // حذف اگر مجاز نیست
            }

            // 5. ذخیره تغییرات
            $course->update($data);

            // 6. مدیریت جابجایی دسته‌بندی (شمارنده)
            $newCategoryId = $data['category_id'] ?? $oldCategoryId;
            if ((int)$oldCategoryId !== (int)$newCategoryId) {
                Category::where('id', $oldCategoryId)->decrement('courses_count');
                Category::where('id', $newCategoryId)->increment('courses_count');
            }

            return $course;
        });
    }

    /**
     * حذف موقت (Soft Delete)
     */
    public function deleteCourse(Course $course): void
    {
        DB::transaction(function () use ($course) {
            $categoryId = $course->category_id;

            $course->delete(); // سافت دیلیت

            // کاهش تعداد دوره‌ها در دسته‌بندی
            if ($categoryId) {
                Category::where('id', $categoryId)->decrement('courses_count');
            }
        });
    }

    /**
     * بازیابی دوره (Restore)
     */
    public function restoreCourse(Course $course): void
    {
        DB::transaction(function () use ($course) {
            $course->restore();

            // چون برگشت، دوباره تعداد رو زیاد کن
            if ($course->category_id) {
                Category::where('id', $course->category_id)->increment('courses_count');
            }
        });
    }

    /**
     * حذف دائم (Force Delete)
     */
    public function forceDeleteCourse(Course $course): void
    {
        DB::transaction(function () use ($course) {
            // 1. حذف فیزیکی عکس
            if ($course->thumbnail) {
                $oldPath = str_replace(url('storage/'), '', $course->thumbnail);
                Storage::disk('public')->delete($oldPath);
            }

            // 2. حذف سئو
            if (method_exists($course, 'seo')) {
                $course->seo()->delete();
            }

            // 3. کاهش تعداد (فقط اگر دوره فعال بوده و مستقیم حذف دائم شده)
            // اگر قبلاً سافت دیلیت شده باشد، تعدادش قبلاً کم شده است.
            if (!$course->trashed() && $course->category_id) {
                Category::where('id', $course->category_id)->decrement('courses_count');
            }

            // 4. حذف نهایی از دیتابیس
            $course->forceDelete();
        });
    }
}
