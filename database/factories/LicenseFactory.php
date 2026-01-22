<?php

namespace Database\Factories;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<License>
 */
class LicenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'package_id' => Package::factory(),
            'license_key' => strtoupper(Str::random(8).'-'.Str::random(8).'-'.Str::random(8).'-'.Str::random(8)),
            'domain_limit' => fake()->randomElement([1, 3, 5, 10, null]),
            'status' => LicenseStatus::Active,
            'expires_at' => fake()->optional(0.7)->dateTimeBetween('+1 month', '+1 year'),
            'last_validated_at' => fake()->optional()->dateTimeBetween('-7 days', 'now'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LicenseStatus::Active,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LicenseStatus::Suspended,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LicenseStatus::Expired,
            'expires_at' => fake()->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LicenseStatus::Cancelled,
        ]);
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'domain_limit' => null,
        ]);
    }
}
