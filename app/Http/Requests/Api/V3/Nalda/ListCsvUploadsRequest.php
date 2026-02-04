<?php

namespace App\Http\Requests\Api\V3\Nalda;

use App\Enums\NaldaCsvType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListCsvUploadsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string', 'max:255'],
            'product_slug' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', Rule::enum(NaldaCsvType::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'license_key.required' => 'A license key is required.',
            'product_slug.required' => 'A product slug is required.',
            'domain.required' => 'A domain is required.',
            'per_page.integer' => 'Per page must be a valid number.',
            'per_page.min' => 'Per page must be at least 1.',
            'per_page.max' => 'Per page must not exceed 100.',
        ];
    }
}
