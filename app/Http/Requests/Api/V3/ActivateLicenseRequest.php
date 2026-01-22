<?php

namespace App\Http\Requests\Api\V3;

use Illuminate\Foundation\Http\FormRequest;

class ActivateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string', 'max:255'],
            'product_slug' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'license_key.required' => 'A license key is required.',
            'license_key.string' => 'The license key must be a valid string.',
            'product_slug.required' => 'A product slug is required.',
            'product_slug.string' => 'The product slug must be a valid string.',
            'domain.required' => 'A domain is required for activation.',
            'domain.string' => 'The domain must be a valid string.',
        ];
    }
}
