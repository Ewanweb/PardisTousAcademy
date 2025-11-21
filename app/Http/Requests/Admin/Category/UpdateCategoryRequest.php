<?php

namespace App\Http\Requests\Admin\Category;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Admin', 'Manager']);
    }

    public function rules(): array
    {
        // دسترسی به مدل دسته‌بندی که در روت است
        $categoryId = $this->route('category')->id;

        return [
            'name' => ['required', 'string', 'max:255'],

            // والد می‌تواند null باشد، اما اگر عددی بود:
            // 1. باید در جدول باشد
            // 2. نباید خودش باشد (جلوگیری از باگ منطقی)
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($categoryId) {
                    if ((int) $value === $categoryId) {
                        $fail('یک دسته‌بندی نمی‌تواند زیرمجموعه خودش باشد.');
                    }
                }
            ],

            'is_active' => ['boolean'],

            // اطلاعات سئو
            'seo' => ['nullable', 'array'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string'],
            'seo.noindex' => ['boolean'],
        ];
    }
}
