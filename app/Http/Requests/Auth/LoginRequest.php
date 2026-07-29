<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'identifier' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (!is_string($value)) {
                        $fail(__('auth.invalid_credentials'));
                        return;
                    }

                    if (str_starts_with($value, '+')) {
                        if (!preg_match('/^\+[1-9]\d{7,14}$/', $value)) {
                            $fail(__('validation.regex', ['attribute' => $attribute]));
                        }
                        return;
                    }

                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $fail(__('validation.email', ['attribute' => $attribute]));
                    }
                },
            ],
            'password' => 'required|string',
        ];
    }

    /**
     * Normalise the login identifier.
     *
     * The canonical field is `identifier` (accepts an e-mail address or an
     * E.164 phone number, matching AuthService::login()). For backwards
     * compatibility with the documented `{"email": ..., "password": ...}`
     * payload (docs/api/API_DOCUMENTATION.md) we also accept `email` and
     * `phone` and fold them into `identifier`.
     */
    protected function prepareForValidation(): void
    {
        $identifier = $this->input('identifier');

        if (!is_string($identifier) || trim($identifier) === '') {
            foreach (['email', 'phone'] as $alias) {
                $candidate = $this->input($alias);
                if (is_string($candidate) && trim($candidate) !== '') {
                    $identifier = $candidate;
                    break;
                }
            }
        }

        if (is_string($identifier)) {
            $identifier = preg_replace('/\s+/', '', trim($identifier));
        }

        $this->merge([
            'identifier' => $identifier,
        ]);
    }
}
