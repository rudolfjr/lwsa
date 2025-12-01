<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $costPrice = fake()->randomFloat(2, 10, 500);
        $marginPercent = fake()->randomFloat(2, 20, 80);
        $salePrice = $costPrice * (1 + $marginPercent / 100);

        return [
            'sku' => strtoupper(fake()->unique()->bothify('PRD-####-??')),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'cost_price' => round($costPrice, 2),
            'sale_price' => round($salePrice, 2),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
