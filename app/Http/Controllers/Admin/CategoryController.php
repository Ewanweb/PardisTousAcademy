<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Category\DeleteCategoryRequest;
use App\Http\Requests\Admin\Category\StoreCategoryRequest;
use App\Http\Requests\Admin\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Admin\Category;
use App\Services\Category\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * لیست دسته‌بندی‌ها
     */
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->with(['children', 'seo']) // فرزندان و سئو را هم بیار
            ->get();

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * ایجاد دسته‌بندی جدید
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->createCategory($request->validated(), $request->user());

        return response()->json([
            'message' => 'دسته‌بندی با موفقیت ایجاد شد.',
            'data' => new CategoryResource($category->load('seo', 'creator')),
        ], 201);
    }

    /**
     * نمایش دسته بندی خاص
     */
    public function show(Category $category)
    {
        $category->load(['seo', 'children']);
        return response()->json([
            'message' => 'دسته‌بندی با موفقیت نمایش داده شد.',
            'data' => new CategoryResource($category),
        ], 201);
    }

    /**
     * نمایش زیرمجموعه‌های یک دسته‌بندی خاص
     */
    public function children(Category $category): JsonResponse
    {
        // گرفتن فرزندان به همراه سئو و تعداد دوره‌هایشان
        $children = $category->children()
            ->with(['seo']) // اگر نیاز دارید فرزندانِ فرزندان را هم ببینید: 'children'
            ->withCount('courses')
            ->latest() // جدیدترین‌ها اول
            ->get(); // یا ->paginate(20) برای پروژه‌های خیلی بزرگ

        return response()->json([
            'message' => "زیرمجموعه‌های دسته '{$category->name}' دریافت شد.",
            'parent' => new CategoryResource($category), // اطلاعات پدر را هم می‌فرستیم که در هدر سایت نمایش دهید
            'data' => CategoryResource::collection($children),
        ]);
    }


    /**
     * ویرایش دسته بندی
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $updatedCategory = $this->categoryService->updateCategory($category, $request->validated());

        return response()->json([
            'message' => 'دسته‌بندی با موفقیت ویرایش شد.',
            'data' => new CategoryResource($updatedCategory->load('seo')),
        ]);
    }

    /**
     * حذف دسته‌بندی
     * متد DELETE میتواند شامل Body باشد برای تعیین مقصد انتقال
     */
    public function destroy(DeleteCategoryRequest $request, Category $category): JsonResponse
    {
        try {
            // مقدار migrate_to_id را از درخواست میگیریم (ممکن است null باشد)
            $migrateToId = $request->validated()['migrate_to_id'] ?? null;

            $this->categoryService->deleteCategory($category, $migrateToId);

            return response()->json([
                'message' => 'دسته‌بندی با موفقیت حذف شد' . ($migrateToId ? ' و محتوا منتقل گردید.' : '.'),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'CONTENT_DEPENDENCY_ERROR'
            ], 409); // 409 Conflict
        }
    }
}
