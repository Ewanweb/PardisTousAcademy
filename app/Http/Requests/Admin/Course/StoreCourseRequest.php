<?php

namespace App\Http\Requests\Admin\Course;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // ادمین، مدیر یا مدرس اجازه دارند
        return $this->user()->hasAnyRole(['Admin', 'Manager']);
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
                // ✅ قانون حیاتی: نام دوره باید در کل جدول courses یکتا باشد
                'unique:courses,title',
            ],

            'category_id' => ['required', 'exists:categories,id'],
            'price' => ['required', 'integer', 'min:0'],
            'description' => ['required', 'string'],

            // هندل کردن عکس (چه با نام image بیاید چه thumbnail)
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'status' => ['nullable', 'in:draft,published,archived'],
            'instructor_id' => ['nullable', 'exists:users,id'],

            // ✅ اعتبارسنجی سئو (برای ساخت)
            'seo' => ['nullable', 'array'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string'],
            'seo.noindex' => ['boolean'],
            'seo.canonical_url' => ['nullable', 'url'],
        ];
    }

    // ✅ پیام خطای فارسی اختصاصی
    public function messages(): array
    {
        return [
            'title.unique' => 'این نام دوره قبلاً در سیستم ثبت شده است. لطفاً نام دیگری انتخاب کنید.',
            'category_id.exists' => 'دسته‌بندی انتخاب شده معتبر نیست.',
        ];
    }
}
