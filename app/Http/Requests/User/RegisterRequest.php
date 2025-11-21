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
            'mobile' => ['nullable', 'string', 'unique:users', 'regex:/^09[0-9]{9}$/'], // اعتبارسنجی موبایل ایران
            'password' => ['required', 'confirmed', Password::defaults()], // پسورد قوی
        ];
    }
}
