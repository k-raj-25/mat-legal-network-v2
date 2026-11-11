<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LawyerProfileUpdateRequest extends FormRequest
{
    use LawyerProfileRules;

    public function authorize(): bool
    {
        return (bool) $this->user()?->lawyerProfile;
    }

    public function rules(): array
    {
        return [
            ...$this->profileRules($this->user()->lawyerProfile->id),
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }
}
