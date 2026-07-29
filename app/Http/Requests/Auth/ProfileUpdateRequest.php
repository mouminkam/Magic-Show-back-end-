<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            // The unique rule must ignore the current customer, otherwise
            // re-submitting an unchanged phone number fails validation and the
            // customer can never update any other profile field.
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:16',
                'regex:/^\+[1-9]\d{7,14}$/',
                Rule::unique('customers', 'phone')->ignore($this->user()?->getKey()),
            ],
            'address' => 'sometimes|nullable|string|max:500',
            'city' => 'sometimes|nullable|string|max:255',
            'country' => 'sometimes|nullable|string|max:255',
            'postal_code' => 'sometimes|nullable|string|max:20',
        ];
    }

    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');
        if (is_string($phone)) {
            $phone = preg_replace('/\s+/', '', trim($phone));
        }

        $this->merge([
            'phone' => $phone,
        ]);
    }
}
