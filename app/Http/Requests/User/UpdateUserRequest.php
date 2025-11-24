<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Admin', 'Manager']);
    }

    public function rules(): array
    {
        // گرفتن یوزر از روت (مثلا users/5)
        $user = $this->route('user');
        $userId = $user ? $user->id : null;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],

            // ایمیل باید یکتا باشد، به جز ایمیل خودِ همین کاربر
            'email' => [
                'sometimes', 'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],

            'mobile' => [
                'nullable', 'string', 'max:20',
                Rule::unique('users', 'mobile')->ignore($userId),
            ],

            // در ویرایش، پسورد nullable است (یعنی اگر نفرستاد، تغییر نکند)
            'password' => ['nullable', 'confirmed', Password::defaults()],

            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,name'],
            'is_active' => ['boolean'],
        ];
    }
}
