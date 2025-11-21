<?php

namespace App\Http\Controllers\Admin; // یا Api\V1 بسته به پوشه کنترلر

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Course\StoreCourseRequest; // مسیر ریکوئست‌ها را چک کنید
use App\Http\Requests\Admin\Course\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Admin\Category;
use App\Models\Admin\Course;
use App\Services\Course\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    protected CourseService $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    /**
     * دریافت لیست دوره‌ها
     * (با قابلیت فیلتر و صفحه‌بندی)
     */
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $query = Course::query()->with(['instructor', 'category', 'seo']);
        $user = auth('sanctum')->user();

        // اگر کاربر لاگین نکرده یا ادمین/مدیر نیست، فقط منتشر شده‌ها را ببیند
        if (!$user || !$user->hasAnyRole(['Admin', 'Manager'])) {
            $query->published();
        }

        // فیلتر دسته‌بندی
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // دریافت داده‌ها به صورت صفحه‌بندی شده
        $courses = $query->latest()->paginate(12);

        // ✅ روش صحیح: استفاده از collection
        // این روش خودش "data" و "meta" و "links" را می‌سازد
        return CourseResource::collection($courses);
    }

    /**
     * نمایش تکی یک دوره
     */
    public function show(Course $course): JsonResponse
    {
        $this->authorize('view', $course);

        return response()->json([
            'data' => new CourseResource($course->load(['instructor', 'category', 'seo'])),
        ]);
    }

    /**
     *نمایش دوره های یک دسته بندی
     */
    public function courseCategory(Category $category)
    {
        // 1. دریافت آی‌دی تمام زیرمجموعه‌ها (متد getAllChildrenIds باید در مدل Category باشد)
        $categoryIds = $category->getAllChildrenIds();

        // 2. کوئری برای گرفتن دوره‌هایی که در این دسته‌ها هستند
        $query = Course::query()
            ->whereIn('category_id', $categoryIds)
            ->with(['instructor', 'category', 'seo'])
            ->latest();

        // 3. فیلتر امنیتی (منتشر شده‌ها برای کاربران عادی)
        $user = auth('sanctum')->user();
        if (!$user || !$user->hasAnyRole(['Admin', 'Manager'])) {
            $query->where('status', 'published');
        }

        $courses = $query->paginate(12);

        return CourseResource::collection($courses)->additional([
            'category_info' => [
                'id' => $category->id,
                'title' => $category->name,
                'slug' => $category->slug,
                // اگر سئو دارد و نال نیست
                'description' => $category->seo ? $category->seo->meta_description : null,
            ]
        ]);
    }
    /**
     * ایجاد دوره جدید
     */
    public function store(StoreCourseRequest $request): JsonResponse
    {
        // ساخت دوره توسط سرویس
        $course = $this->courseService->createCourse($request->validated(), $request->user());

        return response()->json([
            'message' => 'دوره با موفقیت ایجاد شد.',
            'data' => new CourseResource($course),
        ], 201);
    }

    /**
     * ویرایش دوره
     */
    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
// داده‌های ولیدیت شده (شامل seo و title چک شده) به سرویس می‌روند
        $updatedCourse = $this->courseService->updateCourse($course, $request->validated());

        return response()->json([
            'message' => 'دوره با موفقیت ویرایش شد.',
            // لود کردن سئو برای نمایش در پاسخ
            'data' => new CourseResource($updatedCourse->load('seo')),
        ]);
    }

    /**
     * حذف دوره
     */
    public function destroy(Course $course): JsonResponse
    {
        // استفاده از Policy برای چک کردن دسترسی
        $this->authorize('delete', $course);

        $this->courseService->deleteCourse($course);

        return response()->json(['message' => 'دوره حذف شد.']);
    }
}
