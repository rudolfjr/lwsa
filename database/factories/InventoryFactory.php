<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(10, 500),
            'total_cost_value' => 0,
            'total_sale_value' => 0,
            'projected_profit' => 0,
            'last_movement_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Inventory $inventory) {
            $inventory->recalculate();
            $inventory->save();
        });
    }

    public function stale(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_movement_at' => fake()->dateTimeBetween('-1 year', '-100 days'),
        ]);
    }
}
