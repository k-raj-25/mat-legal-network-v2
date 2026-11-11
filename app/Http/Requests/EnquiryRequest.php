<?php

namespace App\Http\Requests;

use App\Models\Country;
use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'string', 'max:20', new PhoneNumber(Country::default()->iso2)],
            'email' => ['nullable', 'required_if:preferred_contact,email', 'email:rfc', 'max:190'],
            'city' => ['nullable', 'string', 'max:100'],
            'practice_area_id' => ['nullable', 'integer', Rule::exists('practice_areas', 'id')],
            'preferred_contact' => ['required', Rule::in(['call', 'whatsapp', 'email'])],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'consent.accepted' => 'Please agree to the Privacy Policy and Disclaimer so we can contact you.',
            'email.required_if' => 'Please enter your email if you prefer to be contacted by email.',
        ];
    }
}
