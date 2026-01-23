<?php

namespace Database\Factories;

use App\Enums\NaldaCsvType;
use App\Models\License;
use App\Models\NaldaCsvUpload;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NaldaCsvUpload>
 */
class NaldaCsvUploadFactory extends Factory
{
    protected $model = NaldaCsvUpload::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'domain' => fake()->domainName(),
            'csv_type' => fake()->randomElement(NaldaCsvType::cases()),
            'sftp_host' => 'sftp.nalda.com',
            'sftp_port' => 22,
            'sftp_username' => fake()->userName(),
            'sftp_path' => null,
            'status' => 'pending',
            'error_message' => null,
            'uploaded_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'sftp_path' => '/order-status/'.fake()->uuid().'.csv',
            'uploaded_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => 'SFTP connection failed',
        ]);
    }

    public function orders(): static
    {
        return $this->state(fn (array $attributes) => [
            'csv_type' => NaldaCsvType::Orders,
        ]);
    }

    public function products(): static
    {
        return $this->state(fn (array $attributes) => [
            'csv_type' => NaldaCsvType::Products,
        ]);
    }
}
