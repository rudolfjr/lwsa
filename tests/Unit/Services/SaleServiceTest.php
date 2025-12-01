<?php

namespace Tests\Unit\Services;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Repositories\Eloquent\InventoryRepository;
use App\Repositories\Eloquent\ProductRepository;
use App\Repositories\Eloquent\SaleRepository;
use App\Services\InventoryService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    private SaleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $inventoryService = new InventoryService(
            new InventoryRepository(new Inventory()),
            new ProductRepository(new Product())
        );

        $this->service = new SaleService(
            new SaleRepository(new Sale()),
            new ProductRepository(new Product()),
            $inventoryService
        );
    }

    public function test_create_sale_creates_pending_sale(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create([
            'cost_price' => 100,
            'sale_price' => 150,
        ]);
        Inventory::create([
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        $sale = $this->service->createSale([
            ['product_id' => $product->id, 'quantity' => 5],
        ]);

        $this->assertEquals(Sale::STATUS_PENDING, $sale->status);
        $this->assertEquals(750, $sale->total_amount);
        $this->assertEquals(1, $sale->items->count());
    }

    public function test_create_sale_throws_exception_for_insufficient_stock(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create();
        Inventory::create([
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->service->createSale([
            ['product_id' => $product->id, 'quantity' => 10],
        ]);
    }

    public function test_process_sale_completes_sale_and_updates_stock(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create([
            'cost_price' => 100,
            'sale_price' => 150,
        ]);
        Inventory::create([
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        $sale = Sale::create([
            'code' => 'TEST-001',
            'status' => Sale::STATUS_PENDING,
            'user_id' => $user->id,
        ]);
        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 150,
            'unit_cost' => 100,
            'subtotal' => 1500,
            'profit' => 500,
        ]);

        $result = $this->service->processSale($sale);

        $this->assertEquals(Sale::STATUS_COMPLETED, $result->status);
        $this->assertNotNull($result->completed_at);
        $this->assertEquals(90, Inventory::where('product_id', $product->id)->first()->quantity);
    }

    public function test_cancel_sale_changes_status_to_cancelled(): void
    {
        $user = User::factory()->create();

        $sale = Sale::create([
            'code' => 'TEST-002',
            'status' => Sale::STATUS_PENDING,
            'user_id' => $user->id,
        ]);

        $result = $this->service->cancelSale($sale);

        $this->assertEquals(Sale::STATUS_CANCELLED, $result->status);
    }

    public function test_cancel_completed_sale_throws_exception(): void
    {
        $user = User::factory()->create();

        $sale = Sale::create([
            'code' => 'TEST-003',
            'status' => Sale::STATUS_COMPLETED,
            'user_id' => $user->id,
            'completed_at' => now(),
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->cancelSale($sale);
    }
}
