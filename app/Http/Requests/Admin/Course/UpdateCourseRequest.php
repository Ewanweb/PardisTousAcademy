<?php

namespace App\Http\Requests\Admin\Course;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    /**
     * تعیین اینکه آیا کاربر اجازه ویرایش دارد؟
     * این کار توسط Policy انجام می‌شود
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('course'));
    }

    /**
     * قوانین اعتبارسنجی
     */
    public function rules(): array
    {
        // دریافت آی‌دی دوره‌ای که در حال ویرایش است از آدرس URL
        // مثال: /api/v1/courses/15 -> مقدار 15 را می‌گیرد
        $courseId = $this->route('course')->id;

        return [
            // 1. اعتبارسنجی نام دوره (جلوگیری از تکرار با استثنا کردن خودش)
            'title' => [
                'sometimes', // یعنی اگر کاربر فرستاد چک کن، اگر نفرستاد کاری نداشته باش
                'required',
                'string',
                'max:255',
                // نام باید یکتا باشد، اما آی‌دی خودِ این دوره را نادیده بگیر (Ignore)
                Rule::unique('courses', 'title')->ignore($courseId),
            ],

            // 2. اعتبارسنجی دسته‌بندی
            'category_id' => [
                'sometimes',
                'integer',
                'exists:categories,id' // باید در جدول categories باشد
            ],

            // 3. اعتبارسنجی قیمت و توضیحات
            'price' => ['sometimes', 'integer', 'min:0'],
            'description' => ['sometimes', 'string'],

            // 4. اعتبارسنجی تصویر (ممکن است با نام image یا thumbnail بیاید)
            'image' => ['nullable', 'string'],     // اگر فقط لینک عکس را می‌فرستید

            // 5. اعتبارسنجی وضعیت (فقط مقادیر مجاز)
            'status' => [
                'sometimes',
                Rule::in(['draft', 'published', 'archived'])
            ],

            // 6. اعتبارسنجی سئو (آرایه تودرتو)
            'seo' => ['nullable', 'array'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string'],
            'seo.canonical_url' => ['nullable', 'url'], // باید فرمت لینک باشد
            'seo.noindex' => ['boolean'], // true/false یا 0/1
            'seo.nofollow' => ['boolean'],

            // 7. اعتبارسنجی کدهای اسکیما (اختیاری - اگر جیسون می‌فرستید)
            'seo.schema_markup' => ['nullable', 'array'],
        ];
    }

    /**
     * پیام‌های خطای فارسی
     */
    public function messages(): array
    {
        return [
            'title.required' => 'وارد کردن عنوان دوره الزامی است.',
            'title.unique' => 'این نام برای دوره دیگری انتخاب شده است. لطفاً نام دیگری انتخاب کنید.',
            'category_id.exists' => 'دسته‌بندی انتخاب شده معتبر نیست.',
            'price.integer' => 'مبلغ دوره باید به عدد (تومان) وارد شود.',
            'status.in' => 'وضعیت انتخاب شده معتبر نیست.',
            'seo.canonical_url.url' => 'لینک کانونیکال سئو باید یک آدرس اینترنتی معتبر باشد.',
        ];
    }

}
