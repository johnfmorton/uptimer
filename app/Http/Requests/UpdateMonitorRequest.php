<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMonitorRequest extends FormRequest
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
            'name' => 'sometimes|required|string|max:255',
            'url' => [
                'sometimes',
                'required',
                'url',
                'max:2048',
                'regex:/^https?:\/\//i', // Must start with http:// or https://
                'regex:/^https?:\/\/[^\/]+\.[a-z]{2,}/i', // Must have a TLD
                'not_regex:/^https?:\/\/(localhost|127\.0\.0\.1|::1)/i', // Reject localhost
            ],
            'check_interval_minutes' => 'sometimes|required|integer|min:1|max:1440',
        ];
    }

    /**
     * Get custom error messages for validation failures.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The monitor name is required.',
            'name.string' => 'The monitor name must be a valid text string.',
            'name.max' => 'The monitor name cannot exceed 255 characters.',
            'url.required' => 'The URL to monitor is required.',
            'url.url' => 'The URL must be a valid URL format (e.g., https://example.com).',
            'url.max' => 'The URL cannot exceed 2048 characters.',
            'url.regex' => 'The URL must use HTTP or HTTPS protocol and include a valid domain with a top-level domain (e.g., .com, .org).',
            'url.not_regex' => 'The URL cannot be a localhost address. Please use a publicly accessible URL.',
            'check_interval_minutes.required' => 'The check interval is required.',
            'check_interval_minutes.integer' => 'The check interval must be a whole number.',
            'check_interval_minutes.min' => 'The check interval must be at least 1 minute.',
            'check_interval_minutes.max' => 'The check interval cannot exceed 1440 minutes (24 hours).',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'check_interval_minutes' => 'check interval',
        ];
    }
}
