<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // ثبت‌نام برای همه آزاد است
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],

            'mobile' => ['nullable', 'string', 'unique:users', 'regex:/^09[0-9]{9}$/'],

            // پسورد قوی (حداقل ۸ کاراکتر + تاییدیه)
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'این ایمیل قبلاً ثبت نام کرده است.',
            'mobile.regex' => 'فرمت شماره موبایل صحیح نیست (مثال: 09123456789).',
            'password.confirmed' => 'تکرار رمز عبور مطابقت ندارد.',
        ];
    }
}
