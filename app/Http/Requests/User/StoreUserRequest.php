<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // فقط ادمین و منیجر
        return $this->user()->hasAnyRole(['Admin', 'Manager']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email', 'max:255'],
            'mobile' => ['nullable', 'string', 'unique:users,mobile', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,name'], // نقش باید معتبر باشد
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'mobile.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
            'password.confirmed' => 'تکرار رمز عبور مطابقت ندارد.',
        ];
    }
}
