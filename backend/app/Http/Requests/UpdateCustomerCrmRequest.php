<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerCrmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'is_vip' => ['sometimes', 'boolean'],
            'is_blacklisted' => ['sometimes', 'boolean'],
            'birthday_month' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:12'],
            'birthday_day' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:31'],
            'preferred_table_notes' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'notes.max' => 'Les notes ne peuvent pas dépasser 2000 caractères.',
            'birthday_month.min' => 'Le mois d’anniversaire doit être compris entre 1 et 12.',
            'birthday_month.max' => 'Le mois d’anniversaire doit être compris entre 1 et 12.',
            'birthday_day.min' => 'Le jour d’anniversaire doit être compris entre 1 et 31.',
            'birthday_day.max' => 'Le jour d’anniversaire doit être compris entre 1 et 31.',
            'preferred_table_notes.max' => 'La note de table préférée ne peut pas dépasser 255 caractères.',
        ];
    }
}
