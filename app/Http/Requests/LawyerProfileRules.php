<?php

namespace App\Http\Requests;

use App\Models\Country;
use App\Rules\PhoneNumber;
use Illuminate\Validation\Rule;

trait LawyerProfileRules
{
    protected function profileRules(?int $ignoreLawyerId = null): array
    {
        $docMimes = implode(',', config('mat.uploads.document_mimes'));
        $country = Country::default();

        return [
            'full_name' => ['required', 'string', 'min:3', 'max:120'],
            'gender' => ['nullable', Rule::in(array_keys(config('mat.genders')))],
            'designation' => ['nullable', Rule::in(config('mat.designations'))],
            'firm_name' => ['nullable', 'string', 'max:150'],
            'practice_since' => ['required', 'integer', 'between:1950,'.now()->year],
            'education' => ['nullable', 'string', 'max:200'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'consultation_fee' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'offers_online_consultation' => ['nullable', 'boolean'],
            'state_id' => ['required', 'integer', Rule::exists('states', 'id')->where('country_id', $country->id)->where('is_active', true)],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')->where('state_id', (int) $this->input('state_id'))->where('is_active', true)],
            'phone' => ['required', 'string', 'max:20', new PhoneNumber($country->iso2)],
            'alternate_phone' => ['nullable', 'string', 'max:20', new PhoneNumber($country->iso2)],
            'office_address' => ['nullable', 'string', 'max:500'],
            'pincode' => ['nullable', 'regex:/^\d{6}$/'],
            'enrolment_number' => ['required', 'string', 'max:60', Rule::unique('lawyer_profiles', 'enrolment_number')->ignore($ignoreLawyerId)],
            'bar_council' => ['required', 'string', 'max:150'],
            'practice_areas' => ['required', 'array', 'min:1', 'max:10'],
            'practice_areas.*' => ['integer', Rule::exists('practice_areas', 'id')->where('is_active', true)],
            'languages' => ['required', 'array', 'min:1', 'max:10'],
            'languages.*' => ['integer', Rule::exists('languages', 'id')->where('is_active', true)],
            'courts' => ['nullable', 'array', 'max:10'],
            'courts.*' => ['integer', Rule::exists('courts', 'id')->where('is_active', true)],
            'photo' => ['nullable', 'image', 'mimes:'.implode(',', config('mat.uploads.photo_mimes')), 'max:'.config('mat.uploads.photo_max_kb'), 'dimensions:max_width=6000,max_height=6000'],
            'enrolment_certificate' => ['nullable', 'file', 'mimes:'.$docMimes, 'max:'.config('mat.uploads.document_max_kb')],
            'id_proof' => ['nullable', 'file', 'mimes:'.$docMimes, 'max:'.config('mat.uploads.document_max_kb')],
            'other' => ['nullable', 'file', 'mimes:'.$docMimes, 'max:'.config('mat.uploads.document_max_kb')],
        ];
    }

    public function attributes(): array
    {
        return [
            'state_id' => 'state', 'city_id' => 'city', 'practice_since' => 'practising since',
            'practice_areas' => 'practice areas', 'enrolment_certificate' => 'enrolment certificate',
            'id_proof' => 'ID proof', 'accept_terms' => 'terms', 'declaration' => 'declaration',
        ];
    }

    public function messages(): array
    {
        return [
            'enrolment_number.unique' => 'A lawyer with this enrolment number is already registered. Please contact support if this is you.',
            'accept_terms.accepted' => 'You must accept the Terms of Use, Lawyer Listing Terms and Privacy Policy.',
            'declaration.accepted' => 'You must confirm that the information you provided is true.',
        ];
    }
}
