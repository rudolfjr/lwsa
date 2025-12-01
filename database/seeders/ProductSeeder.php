<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating 100 products with inventory...');

        $products = Product::factory(100)->create();

        $bar = $this->command->getOutput()->createProgressBar($products->count());

        foreach ($products as $product) {
            $quantity = fake()->numberBetween(50, 500);

            $inventory = Inventory::create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'last_movement_at' => fake()->dateTimeBetween('-6 months', 'now'),
            ]);
            $inventory->recalculate();
            $inventory->save();

            InventoryMovement::create([
                'product_id' => $product->id,
                'type' => InventoryMovement::TYPE_ENTRY,
                'quantity' => $quantity,
                'unit_cost' => $product->cost_price,
                'reference_type' => InventoryMovement::REFERENCE_MANUAL,
                'notes' => 'Initial stock entry',
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Products and inventory created successfully!');
    }
}
