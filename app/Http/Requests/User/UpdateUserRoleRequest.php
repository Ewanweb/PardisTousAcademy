<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // فقط مدیر و ادمین اجازه دارند نقش‌ها را عوض کنند
        return $this->user()->hasAnyRole(['Manager']);
    }

    public function rules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'], // حداقل یک نقش باید باشد
            'roles.*' => ['required', 'string', 'exists:roles,name'], // هر نقش باید معتبر باشد
        ];
    }

    public function messages(): array
    {
        return [
            'roles.required' => 'انتخاب حداقل یک نقش الزامی است.',
            'roles.*.exists' => 'یکی از نقش‌های انتخاب شده در سیستم وجود ندارد.',
        ];
    }
}
