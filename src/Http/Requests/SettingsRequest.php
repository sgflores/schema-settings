<?php

namespace SgFlores\SchemaSetting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * SettingsRequest - Form Request for API Validation
 *
 * This form request handles validation for the Settings API endpoints.
 * It validates both single key requests and multiple key requests
 * with appropriate rules and error messages.
 *
 * Validation Rules:
 * - 'key': Optional string, max 255 characters
 * - 'keys': Optional array, max 50 items
 * - 'keys.*': Required string, max 255 characters each
 *
 * Features:
 * - Allows empty requests (returns all settings)
 * - Validates parameter types and limits
 * - Provides custom error messages
 * - Extracts keys for processing
 */
class SettingsRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'key' => 'sometimes|string|max:255',
            'keys' => 'sometimes|array|max:50',
            'keys.*' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'key.string' => 'The key parameter must be a string.',
            'key.max' => 'The key parameter must not exceed 255 characters.',
            'keys.array' => 'The keys parameter must be an array.',
            'keys.max' => 'You can request a maximum of 50 settings at once.',
            'keys.*.required' => 'Each key in the array must have a value.',
            'keys.*.string' => 'Each key must be a string.',
            'keys.*.max' => 'Each key must not exceed 255 characters.',
        ];
    }

    /**
     * Get the validated keys (key or keys).
     */
    public function getKeys(): array
    {
        $keys = [];

        if ($this->has('key')) {
            $keys[] = $this->input('key');
        }

        if ($this->has('keys')) {
            $keys = array_merge($keys, $this->input('keys', []));
        }

        return array_filter($keys);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Filter empty keys before validation
        if ($this->has('keys') && is_array($this->input('keys'))) {
            $this->merge(['keys' => array_filter($this->input('keys'))]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        // Allow empty requests - they will return all settings
        // No additional validation needed
    }
}
