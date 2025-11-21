<?php

namespace App\Policies;

use App\Models\Admin\Course;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CoursePolicy
{
    public function view(?User $user, Course $course): bool
    {
        // اگر دوره منتشر شده است، همه (حتی مهمان) می‌توانند ببینند
        if ($course->status === 'published') {
            return true;
        }

        // اگر پیش‌نویس است، فقط ادمین، مدیر یا صاحب دوره ببیند
        return $user && ($user->hasAnyRole(['Admin'|'Manager']) || $user->id === $course->instructor_id);
    }

    public function create(User $user): bool
    {
        // ادمین یا مدرس می‌توانند بسازند
        return $user->hasAnyRole(['Admin'|'Manager',]);
    }

    public function update(User $user, Course $course): bool
    {
        // ادمین همه را می‌تواند ویرایش کند
        if ($user->hasAnyRole(['Admin' | 'Manager'])) {
            return true;
        }
        // مدرس فقط دوره خودش را
        return $user->id === $course->instructor_id;
    }

    public function delete(User $user, Course $course): bool
    {
        // دقیقاً مشابه ویرایش
        if ($user->hasAnyRole(['Admin' | 'Manager'])) {
            return true;
        }
    }
}
