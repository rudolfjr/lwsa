<?php

namespace Tests\Feature\Api;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Queue::fake();
    }

    public function test_can_create_sale(): void
    {
        $product = Product::factory()->create([
            'cost_price' => 100,
            'sale_price' => 150,
        ]);
        Inventory::create([
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 5],
                ],
            ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'code', 'status', 'total_amount'],
            ]);

        $this->assertEquals('pending', $response->json('data.status'));
    }

    public function test_cannot_create_sale_with_insufficient_stock(): void
    {
        $product = Product::factory()->create();
        Inventory::create([
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 10],
                ],
            ]);

        $response->assertStatus(500);
    }

    public function test_can_get_sale_details(): void
    {
        $product = Product::factory()->create();
        $sale = Sale::create([
            'code' => 'TEST-001',
            'status' => Sale::STATUS_COMPLETED,
            'total_amount' => 1000,
            'total_cost' => 700,
            'profit_margin' => 300,
            'profit_percentage' => 42.86,
            'user_id' => $this->user->id,
            'completed_at' => now(),
        ]);
        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 200,
            'unit_cost' => 140,
            'subtotal' => 1000,
            'profit' => 300,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sales/{$sale->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'code',
                    'status',
                    'total_amount',
                    'items',
                ],
            ]);
    }

    public function test_returns_404_for_nonexistent_sale(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sales/9999');

        $response->assertStatus(404);
    }

    public function test_cannot_create_sale_without_items(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales', [
                'items' => [],
            ]);

        $response->assertStatus(422);
    }
}
