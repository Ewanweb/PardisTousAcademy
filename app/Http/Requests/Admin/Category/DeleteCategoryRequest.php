<?php

namespace App\Http\Requests\Admin\Category;

use Illuminate\Foundation\Http\FormRequest;

class DeleteCategoryRequest extends FormRequest
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
        // آیدی دسته‌بندی که قرار است حذف شود
        $currentCategoryId = $this->route('category')->id;

        return [
            // این فیلد اختیاری است، اما اگر ارسال شد:
            // 1. باید عدد باشد
            // 2. باید در جدول وجود داشته باشد
            // 3. نباید آی‌دی خودش باشد (نمیشود محتوا را به خودش منتقل کرد و بعد حذفش کرد!)
            'migrate_to_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                "not_in:$currentCategoryId"
            ],
        ];
    }
}
