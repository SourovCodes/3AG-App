<?php

namespace App\Http\Requests\Api\V3\Nalda;

use App\Enums\NaldaCsvType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadCsvRequest extends FormRequest
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
            'csv_type' => ['required', 'string', Rule::enum(NaldaCsvType::class)],
            'sftp_host' => ['required', 'string', 'max:255', 'regex:/.*\.nalda\.com$/i'],
            'sftp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'sftp_username' => ['required', 'string', 'max:255'],
            'sftp_password' => ['required', 'string', 'max:255'],
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'], // 10MB max
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
            'csv_type.required' => 'CSV type is required.',
            'csv_type.enum' => 'CSV type must be either "orders" or "products".',
            'sftp_host.required' => 'SFTP hostname is required.',
            'sftp_host.regex' => 'SFTP hostname must be a *.nalda.com domain.',
            'sftp_port.integer' => 'SFTP port must be a valid port number.',
            'sftp_port.min' => 'SFTP port must be between 1 and 65535.',
            'sftp_port.max' => 'SFTP port must be between 1 and 65535.',
            'sftp_username.required' => 'SFTP username is required.',
            'sftp_password.required' => 'SFTP password is required.',
            'csv_file.required' => 'A CSV file is required.',
            'csv_file.file' => 'The uploaded file is invalid.',
            'csv_file.mimes' => 'The file must be a CSV file.',
            'csv_file.max' => 'The CSV file must not exceed 10MB.',
        ];
    }
}
