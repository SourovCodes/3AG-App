<?php

namespace App\Http\Requests\Api\V3\Nalda;

use Illuminate\Foundation\Http\FormRequest;

class ValidateSftpRequest extends FormRequest
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
            'sftp_host' => ['required', 'string', 'max:255', 'regex:/.*\.nalda\.com$/i'],
            'sftp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'sftp_username' => ['required', 'string', 'max:255'],
            'sftp_password' => ['required', 'string', 'max:255'],
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
            'sftp_host.required' => 'SFTP hostname is required.',
            'sftp_host.regex' => 'SFTP hostname must be a *.nalda.com domain.',
            'sftp_port.integer' => 'SFTP port must be a valid port number.',
            'sftp_port.min' => 'SFTP port must be between 1 and 65535.',
            'sftp_port.max' => 'SFTP port must be between 1 and 65535.',
            'sftp_username.required' => 'SFTP username is required.',
            'sftp_password.required' => 'SFTP password is required.',
        ];
    }
}
