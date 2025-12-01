<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Repositories\Contracts\InventoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    private const CACHE_KEY = 'inventory:all';
    private const CACHE_TTL = 300;

    public function __construct(
        protected InventoryRepositoryInterface $inventoryRepository,
        protected ProductRepositoryInterface $productRepository
    ) {}

    public function getAllInventory(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->inventoryRepository->getAllWithProducts();
        });
    }

    public function getInventorySummary(): array
    {
        $inventory = $this->getAllInventory();

        return [
            'total_products' => $inventory->count(),
            'total_quantity' => $inventory->sum('quantity'),
            'total_cost_value' => $inventory->sum('total_cost_value'),
            'total_sale_value' => $inventory->sum('total_sale_value'),
            'projected_profit' => $inventory->sum('projected_profit'),
            'items' => $inventory,
        ];
    }

    public function addStock(array $data): Inventory
    {
        return DB::transaction(function () use ($data) {
            $product = $this->productRepository->find($data['product_id']);

            if (!$product) {
                throw new \InvalidArgumentException('Product not found');
            }

            $inventory = $this->inventoryRepository->lockForUpdate($product->id);

            if (!$inventory) {
                $inventory = $this->inventoryRepository->create([
                    'product_id' => $product->id,
                    'quantity' => 0,
                ]);
            }

            $newQuantity = $inventory->quantity + $data['quantity'];
            $inventory = $this->inventoryRepository->updateQuantity($inventory, $newQuantity);

            InventoryMovement::create([
                'product_id' => $product->id,
                'type' => InventoryMovement::TYPE_ENTRY,
                'quantity' => $data['quantity'],
                'unit_cost' => $data['unit_cost'] ?? $product->cost_price,
                'reference_type' => InventoryMovement::REFERENCE_MANUAL,
                'notes' => $data['notes'] ?? null,
                'user_id' => auth()->id(),
            ]);

            AuditLog::log(
                AuditLog::ACTION_CREATE,
                'InventoryMovement',
                $inventory->id,
                null,
                ['quantity_added' => $data['quantity'], 'new_total' => $newQuantity]
            );

            $this->clearCache();

            return $inventory->load('product');
        });
    }

    public function removeStock(int $productId, int $quantity, string $referenceType, ?int $referenceId = null): Inventory
    {
        return DB::transaction(function () use ($productId, $quantity, $referenceType, $referenceId) {
            $inventory = $this->inventoryRepository->lockForUpdate($productId);

            if (!$inventory) {
                throw new \InvalidArgumentException('Inventory not found for this product');
            }

            if ($inventory->quantity < $quantity) {
                throw new \InvalidArgumentException('Insufficient stock');
            }

            $newQuantity = $inventory->quantity - $quantity;
            $inventory = $this->inventoryRepository->updateQuantity($inventory, $newQuantity);

            InventoryMovement::create([
                'product_id' => $productId,
                'type' => InventoryMovement::TYPE_EXIT,
                'quantity' => $quantity,
                'unit_cost' => $inventory->product->cost_price,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'user_id' => auth()->id(),
            ]);

            $this->clearCache();

            return $inventory;
        });
    }

    public function checkAvailability(int $productId, int $quantity): bool
    {
        $inventory = $this->inventoryRepository->findByProductId($productId);
        return $inventory && $inventory->quantity >= $quantity;
    }

    public function getStaleInventory(int $days = 90): Collection
    {
        return $this->inventoryRepository->getStaleInventory($days);
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
