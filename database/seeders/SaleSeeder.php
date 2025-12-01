<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating 10,000 sales with items...');

        $user = User::first() ?? User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->error('No products found. Run ProductSeeder first.');
            return;
        }

        $totalSales = 10000;
        $batchSize = 500;
        $batches = ceil($totalSales / $batchSize);

        $bar = $this->command->getOutput()->createProgressBar($totalSales);

        for ($batch = 0; $batch < $batches; $batch++) {
            $salesToCreate = min($batchSize, $totalSales - ($batch * $batchSize));
            $sales = [];
            $items = [];

            for ($i = 0; $i < $salesToCreate; $i++) {
                $createdAt = Carbon::now()->subMonths(rand(0, 11))->subDays(rand(0, 30));

                $saleData = [
                    'code' => sprintf('SAL-%s-%06d', $createdAt->format('Ymd'), ($batch * $batchSize) + $i + 1),
                    'status' => Sale::STATUS_COMPLETED,
                    'total_amount' => 0,
                    'total_cost' => 0,
                    'profit_margin' => 0,
                    'profit_percentage' => 0,
                    'user_id' => $user->id,
                    'completed_at' => $createdAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                $sale = Sale::create($saleData);

                $numItems = rand(2, 5);
                $selectedProducts = $products->random($numItems);
                $totalAmount = 0;
                $totalCost = 0;

                foreach ($selectedProducts as $product) {
                    $quantity = rand(1, 5);
                    $subtotal = $product->sale_price * $quantity;
                    $cost = $product->cost_price * $quantity;
                    $profit = $subtotal - $cost;

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $product->sale_price,
                        'unit_cost' => $product->cost_price,
                        'subtotal' => $subtotal,
                        'profit' => $profit,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);

                    $totalAmount += $subtotal;
                    $totalCost += $cost;
                }

                $sale->update([
                    'total_amount' => $totalAmount,
                    'total_cost' => $totalCost,
                    'profit_margin' => $totalAmount - $totalCost,
                    'profit_percentage' => $totalCost > 0 ? (($totalAmount - $totalCost) / $totalCost) * 100 : 0,
                ]);

                $bar->advance();
            }
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Sales created successfully!');
    }
}
