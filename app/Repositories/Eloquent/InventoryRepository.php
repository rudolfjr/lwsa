<?php

namespace App\Repositories\Eloquent;

use App\Models\Inventory;
use App\Models\Product;
use App\Repositories\Contracts\InventoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InventoryRepository implements InventoryRepositoryInterface
{
    public function __construct(
        protected Inventory $model
    ) {}

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function getAllWithProducts(): Collection
    {
        return $this->model->with('product')->get();
    }

    public function find(int $id): ?Inventory
    {
        return $this->model->find($id);
    }

    public function findByProduct(Product $product): ?Inventory
    {
        return $this->model->where('product_id', $product->id)->first();
    }

    public function findByProductId(int $productId): ?Inventory
    {
        return $this->model->where('product_id', $productId)->first();
    }

    public function create(array $data): Inventory
    {
        return $this->model->create($data);
    }

    public function update(Inventory $inventory, array $data): Inventory
    {
        $inventory->update($data);
        return $inventory->fresh();
    }

    public function updateQuantity(Inventory $inventory, int $quantity): Inventory
    {
        $inventory->quantity = $quantity;
        $inventory->recalculate();
        $inventory->last_movement_at = now();
        $inventory->save();

        return $inventory;
    }

    public function getStaleInventory(int $days = 90): Collection
    {
        return $this->model
            ->where('last_movement_at', '<', now()->subDays($days))
            ->orWhereNull('last_movement_at')
            ->get();
    }

    public function lockForUpdate(int $productId): ?Inventory
    {
        return $this->model
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();
    }
}
