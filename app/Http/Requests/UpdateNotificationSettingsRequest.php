<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingsRequest extends FormRequest
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
            'email_enabled' => 'required|boolean',
            'email_address' => 'nullable|email|max:255',
            'pushover_enabled' => 'required|boolean',
            'pushover_user_key' => 'nullable|string|max:255',
            'pushover_api_token' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email_enabled.required' => 'Email notification preference is required.',
            'email_enabled.boolean' => 'Email notification preference must be true or false.',
            'email_address.email' => 'Please provide a valid email address.',
            'email_address.max' => 'Email address must not exceed 255 characters.',
            'pushover_enabled.required' => 'Pushover notification preference is required.',
            'pushover_enabled.boolean' => 'Pushover notification preference must be true or false.',
            'pushover_user_key.max' => 'Pushover user key must not exceed 255 characters.',
            'pushover_api_token.max' => 'Pushover API token must not exceed 255 characters.',
        ];
    }
}
