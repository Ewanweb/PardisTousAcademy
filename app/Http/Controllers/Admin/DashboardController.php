<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Category;
use App\Models\Admin\Course;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        // 1. محاسبه آمار و درصد رشد
        $studentsCount = User::count();
        $studentsTrend = $this->calculateGrowth(User::class);

        $activeCoursesCount = Course::count();
        $coursesTrend = $this->calculateGrowth(Course::class);

        $totalRevenue = Course::sum('price');
        $revenueTrend = $this->calculateRevenueGrowth();

        $categoriesCount = Category::count();
        $categoriesTrend = $this->calculateGrowth(Category::class);

        // 2. دریافت فعالیت‌های اخیر (Logs) - ترکیب ۳ مدل مختلف

        // الف) آخرین دوره‌های ایجاد شده
        $latestCourses = Course::with('instructor')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($course) {
                return [
                    'id' => 'course_' . $course->id,
                    'type' => 'course',
                    'title' => "دوره جدید: {$course->title}",
                    'subtitle' => "مدرس: " . ($course->instructor->name ?? 'ناشناس'),
                    'time' => $course->created_at,
                ];
            });

        // ب) آخرین دسته‌بندی‌های ایجاد شده
        $latestCategories = Category::with('creator')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($cat) {
                $creatorName = $cat->creator ? $cat->creator->name : 'سیستم';
                return [
                    'id' => 'cat_' . $cat->id,
                    'type' => 'category',
                    'title' => "دسته‌بندی جدید: {$cat->name}", // دقت کنید در مدل name است یا title
                    'subtitle' => "توسط: {$creatorName}",
                    'time' => $cat->created_at,
                ];
            });

        // ج) آخرین کاربران ثبت‌نامی
        $latestUsers = User::latest()
            ->take(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => 'user_' . $user->id,
                    'type' => 'user',
                    'title' => "کاربر جدید: {$user->name}",
                    'subtitle' => $user->email,
                    'time' => $user->created_at,
                ];
            });

        // د) ترکیب همه، مرتب‌سازی بر اساس زمان و انتخاب 6 تای آخر
        $recentActivity = $latestCourses
            ->merge($latestCategories)
            ->merge($latestUsers)
            ->sortByDesc('time')
            ->take(6)
            ->values();

        // 3. بازگشت پاسخ نهایی
        return response()->json([
            'stats' => [
                'students' => $studentsCount,
                'students_trend' => $studentsTrend,
                'courses' => $activeCoursesCount,
                'courses_trend' => $coursesTrend,
                'revenue' => $totalRevenue,
                'revenue_trend' => $revenueTrend,
                'categories' => $categoriesCount,
                'categories_trend' => $categoriesTrend,
            ],
            'recent_activity' => $recentActivity
        ]);
    }

    /**
     * محاسبه درصد رشد تعداد رکوردها نسبت به ماه قبل
     */
    private function calculateGrowth($model)
    {
        // تعداد ماه جاری
        $currentMonth = $model::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        // تعداد ماه قبل
        $lastMonth = $model::whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->count();

        return $this->calculatePercentage($currentMonth, $lastMonth);
    }

    /**
     * محاسبه درصد رشد مبلغ (Revenue)
     */
    private function calculateRevenueGrowth()
    {
        $currentMonth = Course::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('price');

        $lastMonth = Course::whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->sum('price');

        return $this->calculatePercentage($currentMonth, $lastMonth);
    }

    /**
     * فرمول ریاضی درصد تغییر
     */
    private function calculatePercentage($current, $last)
    {
        if ($last == 0) {
            // اگر ماه قبل هیچی نداشتیم و الان داریم -> 100% رشد
            // اگر کلا هیچی نداریم -> 0%
            return $current > 0 ? 100 : 0;
        }

        // فرمول: ((جدید - قدیم) / قدیم) * 100
        return round((($current - $last) / $last) * 100, 1);
    }
}
