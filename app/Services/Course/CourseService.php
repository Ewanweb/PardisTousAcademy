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

            // 1. آماده سازی داده ها
            $courseData = [
                'title' => $data['title'],
                'slug' => Str::slug($data['title']) . '-' . time(), // یکتا سازی اسلاگ
                'image' => $data['image'],
                'description' => $data['description'],
                'price' => $data['price'],
                'category_id' => $data['category_id'],
                'instructor_id' => $instructor->id,
                'status' => 'draft', // پیش‌فرض پیش‌نویس است
            ];

            // 2. ذخیره در دیتابیس
            $course = Course::create($courseData);

            // 3. (Future) اینجا میتوانید لاجیک‌های دیگر مثل ناتیفیکیشن به ادمین را اضافه کنید
            // Log::info("Course created by {$instructor->name}");
            if (isset($data['seo'])) {
                $course->updateSeo($data['seo']);
            }

            return $course;
        });
    }
}
