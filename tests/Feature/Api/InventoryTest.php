<?php

namespace Tests\Feature\Api;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_get_inventory_list(): void
    {
        $product = Product::factory()->create();
        $inventory = Inventory::create([
            'product_id' => $product->id,
            'quantity' => 100,
        ]);
        $inventory->recalculate();
        $inventory->save();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/inventory');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_products',
                    'total_quantity',
                    'total_cost_value',
                    'total_sale_value',
                    'projected_profit',
                    'items',
                ],
            ]);
    }

    public function test_can_add_stock(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/inventory', [
                'product_id' => $product->id,
                'quantity' => 50,
            ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Stock added successfully']);

        $this->assertDatabaseHas('inventory', [
            'product_id' => $product->id,
            'quantity' => 50,
        ]);
    }

    public function test_cannot_add_stock_with_invalid_product(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/inventory', [
                'product_id' => 9999,
                'quantity' => 50,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_add_negative_quantity(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/inventory', [
                'product_id' => $product->id,
                'quantity' => -10,
            ]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_access_inventory(): void
    {
        $response = $this->getJson('/api/v1/inventory');

        $response->assertStatus(401);
    }
}
