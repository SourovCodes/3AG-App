<?php

namespace Database\Factories;

use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(ProductType::cases()),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function plugin(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::Plugin,
        ]);
    }

    public function theme(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::Theme,
        ]);
    }

    public function sourceCode(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::SourceCode,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
