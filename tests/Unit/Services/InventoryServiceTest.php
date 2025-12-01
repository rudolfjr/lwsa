<?php

namespace Tests\Unit\Services;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Eloquent\InventoryRepository;
use App\Repositories\Eloquent\ProductRepository;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new InventoryService(
            new InventoryRepository(new Inventory()),
            new ProductRepository(new Product())
        );
    }

    public function test_add_stock_creates_inventory_if_not_exists(): void
    {
        $product = Product::factory()->create();

        $result = $this->service->addStock([
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        $this->assertEquals(100, $result->quantity);
        $this->assertDatabaseHas('inventory', [
            'product_id' => $product->id,
            'quantity' => 100,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'type' => 'entry',
            'quantity' => 100,
        ]);
    }

    public function test_add_stock_increases_existing_inventory(): void
    {
        $product = Product::factory()->create();
        Inventory::create([
            'product_id' => $product->id,
            'quantity' => 50,
        ]);

        $result = $this->service->addStock([
            'product_id' => $product->id,
            'quantity' => 30,
        ]);

        $this->assertEquals(80, $result->quantity);
    }

    public function test_remove_stock_decreases_inventory(): void
    {
        $product = Product::factory()->create();
        Inventory::create([
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        $result = $this->service->removeStock(
            $product->id,
            30,
            InventoryMovement::REFERENCE_MANUAL
        );

        $this->assertEquals(70, $result->quantity);
    }

    public function test_remove_stock_throws_exception_for_insufficient_stock(): void
    {
        $product = Product::factory()->create();
        Inventory::create([
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->service->removeStock(
            $product->id,
            50,
            InventoryMovement::REFERENCE_MANUAL
        );
    }

    public function test_check_availability_returns_true_when_stock_sufficient(): void
    {
        $product = Product::factory()->create();
        Inventory::create([
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        $result = $this->service->checkAvailability($product->id, 50);

        $this->assertTrue($result);
    }

    public function test_check_availability_returns_false_when_stock_insufficient(): void
    {
        $product = Product::factory()->create();
        Inventory::create([
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $result = $this->service->checkAvailability($product->id, 50);

        $this->assertFalse($result);
    }

    public function test_get_inventory_summary_returns_correct_data(): void
    {
        $product = Product::factory()->create([
            'cost_price' => 100,
            'sale_price' => 150,
        ]);
        $inventory = Inventory::create([
            'product_id' => $product->id,
            'quantity' => 10,
        ]);
        $inventory->recalculate();
        $inventory->save();

        Cache::flush();
        $result = $this->service->getInventorySummary();

        $this->assertEquals(1, $result['total_products']);
        $this->assertEquals(10, $result['total_quantity']);
        $this->assertEquals(1000, $result['total_cost_value']);
        $this->assertEquals(1500, $result['total_sale_value']);
        $this->assertEquals(500, $result['projected_profit']);
    }
}
