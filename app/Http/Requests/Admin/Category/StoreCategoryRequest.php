<?php

namespace App\Http\Requests\Admin\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // فقط ادمین یا مدیر ارشد اجازه ساخت دسته‌بندی دارد
        return $this->user()->hasAnyRole(['Admin', 'Manager']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // والد می‌تواند null باشد (دسته‌بندی اصلی) یا باید آی‌دی معتبر باشد
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'is_active' => ['boolean'],
            // اعتبارسنجی ورودی‌های سئو (که در مرحله قبل ساختیم)
            'seo' => ['nullable', 'array'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string'],
        ];
    }
}
