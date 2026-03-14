<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWidgetSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logo_url' => ['nullable', 'url', 'max:500'],
            'accent_colour' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'max_party_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'advance_booking_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'same_day_cutoff_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'is_enabled' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'logo_url.url' => "L'URL du logo est invalide.",
            'logo_url.max' => "L'URL du logo ne peut pas dépasser 500 caractères.",
            'accent_colour.regex' => 'La couleur doit être au format hexadécimal (#RRGGBB).',
            'max_party_size.min' => 'Le nombre de couverts minimum est 1.',
            'max_party_size.max' => 'Le nombre de couverts ne peut pas dépasser 100.',
            'advance_booking_days.min' => 'Le délai de réservation minimum est 1 jour.',
            'advance_booking_days.max' => 'Le délai de réservation ne peut pas dépasser 365 jours.',
            'same_day_cutoff_minutes.min' => 'Le délai minimum est 0 minute.',
            'same_day_cutoff_minutes.max' => 'Le délai ne peut pas dépasser 1440 minutes (24h).',
        ];
    }
}
