<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_get_sales_report(): void
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
            ->getJson('/api/v1/reports/sales?' . http_build_query([
                'start_date' => now()->subMonth()->toDateString(),
                'end_date' => now()->toDateString(),
            ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'period',
                    'filters',
                    'summary' => [
                        'total_sales',
                        'total_quantity',
                        'total_amount',
                        'total_profit',
                    ],
                    'by_product',
                ],
            ]);
    }

    public function test_can_filter_report_by_sku(): void
    {
        $product = Product::factory()->create(['sku' => 'TEST-SKU']);
        $sale = Sale::create([
            'code' => 'TEST-002',
            'status' => Sale::STATUS_COMPLETED,
            'total_amount' => 500,
            'user_id' => $this->user->id,
            'completed_at' => now(),
        ]);
        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 250,
            'unit_cost' => 150,
            'subtotal' => 500,
            'profit' => 200,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/reports/sales?' . http_build_query([
                'start_date' => now()->subMonth()->toDateString(),
                'end_date' => now()->toDateString(),
                'sku' => 'TEST-SKU',
            ]));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'filters' => ['sku' => 'TEST-SKU'],
                ],
            ]);
    }

    public function test_report_requires_date_range(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/reports/sales');

        $response->assertStatus(422);
    }

    public function test_report_validates_date_order(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/reports/sales?' . http_build_query([
                'start_date' => now()->toDateString(),
                'end_date' => now()->subMonth()->toDateString(),
            ]));

        $response->assertStatus(422);
    }
}
