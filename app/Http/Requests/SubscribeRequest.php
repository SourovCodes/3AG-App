<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'billing_interval' => [
                'required',
                Rule::in(['monthly', 'yearly']),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'billing_interval.required' => 'Please select a billing interval.',
            'billing_interval.in' => 'Invalid billing interval selected.',
        ];
    }
}
