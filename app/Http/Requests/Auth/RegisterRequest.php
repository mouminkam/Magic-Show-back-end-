<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email',
            'phone' => ['required', 'string', 'max:16', 'regex:/^\+[1-9]\d{7,14}$/', 'unique:customers,phone'],
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');
        if (is_string($phone)) {
            $phone = preg_replace('/\s+/', '', trim($phone));
        }

        $email = $this->input('email');
        if (is_string($email)) {
            $email = trim($email);
        }

        $this->merge([
            'phone' => $phone,
            'email' => $email,
        ]);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'email.unique' => __('auth.email_already_taken'),
            'phone.unique' => __('auth.phone_already_taken'),
        ];
    }
}
