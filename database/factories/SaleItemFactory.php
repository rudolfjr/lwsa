<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleItemFactory extends Factory
{
    protected $model = SaleItem::class;

    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 10),
            'unit_price' => 0,
            'unit_cost' => 0,
            'subtotal' => 0,
            'profit' => 0,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (SaleItem $item) {
            if ($item->product) {
                $item->unit_price = $item->product->sale_price;
                $item->unit_cost = $item->product->cost_price;
                $item->calculateTotals();
            }
        });
    }
}
