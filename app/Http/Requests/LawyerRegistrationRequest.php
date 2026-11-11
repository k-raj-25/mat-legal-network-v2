<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class LawyerRegistrationRequest extends FormRequest
{
    use LawyerProfileRules;

    public function authorize(): bool
    {
        return auth()->guest();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        $rules = $this->profileRules();
        $rules['enrolment_certificate'][0] = 'required';
        $rules['id_proof'][0] = 'required';

        return [
            'email' => ['required', 'string', 'email:rfc', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            ...$rules,
            'accept_terms' => ['accepted'],
            'declaration' => ['accepted'],
        ];
    }
}
