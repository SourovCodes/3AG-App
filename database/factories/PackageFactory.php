<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Package>
 */
class PackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        $monthlyPrice = fake()->randomFloat(2, 9, 99);

        return [
            'product_id' => Product::factory(),
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'domain_limit' => fake()->randomElement([1, 3, 5, 10, null]),
            'stripe_monthly_price_id' => 'price_'.fake()->unique()->regexify('[A-Za-z0-9]{24}'),
            'stripe_yearly_price_id' => 'price_'.fake()->unique()->regexify('[A-Za-z0-9]{24}'),
            'monthly_price' => $monthlyPrice,
            'yearly_price' => round($monthlyPrice * 10, 2),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
            'features' => [
                fake()->sentence(3),
                fake()->sentence(3),
                fake()->sentence(3),
            ],
        ];
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'domain_limit' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
