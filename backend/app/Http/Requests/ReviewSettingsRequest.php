<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReviewSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'review_requests_enabled' => ['required', 'boolean'],
            'review_platform' => ['required', 'in:google,tripadvisor'],
            'review_delay_hours' => ['required', 'integer', 'min:0', 'max:48'],
            'google_place_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tripadvisor_location_id' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->boolean('review_requests_enabled')) {
                    return;
                }

                if ($this->string('review_platform')->toString() === 'google' && blank($this->input('google_place_id'))) {
                    $validator->errors()->add('google_place_id', 'Le Place ID Google est obligatoire lorsque les demandes d’avis sont activées.');
                }

                if ($this->string('review_platform')->toString() === 'tripadvisor' && blank($this->input('tripadvisor_location_id'))) {
                    $validator->errors()->add('tripadvisor_location_id', 'L’identifiant TripAdvisor est obligatoire lorsque les demandes d’avis sont activées.');
                }
            },
        ];
    }
}
