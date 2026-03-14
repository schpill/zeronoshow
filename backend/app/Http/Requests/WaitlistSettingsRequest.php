<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WaitlistSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'waitlist_enabled' => 'required|boolean',
            'waitlist_notification_window_minutes' => 'required|integer|min:5|max:60',
        ];
    }

    public function messages(): array
    {
        return [
            'waitlist_notification_window_minutes.min' => 'Le délai doit être d\'au moins 5 minutes.',
            'waitlist_notification_window_minutes.max' => 'Le délai ne peut pas dépasser 60 minutes.',
        ];
    }
}
