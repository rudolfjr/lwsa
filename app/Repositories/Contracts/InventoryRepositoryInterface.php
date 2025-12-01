<?php

namespace App\Repositories\Contracts;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

interface InventoryRepositoryInterface
{
    public function all(): Collection;

    public function getAllWithProducts(): Collection;

    public function find(int $id): ?Inventory;

    public function findByProduct(Product $product): ?Inventory;

    public function findByProductId(int $productId): ?Inventory;

    public function create(array $data): Inventory;

    public function update(Inventory $inventory, array $data): Inventory;

    public function updateQuantity(Inventory $inventory, int $quantity): Inventory;

    public function getStaleInventory(int $days = 90): Collection;

    public function lockForUpdate(int $productId): ?Inventory;
}
