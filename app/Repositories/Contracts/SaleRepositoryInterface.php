<?php

namespace App\Repositories\Contracts;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SaleRepositoryInterface
{
    public function find(int $id): ?Sale;

    public function findWithItems(int $id): ?Sale;

    public function findByCode(string $code): ?Sale;

    public function create(array $data): Sale;

    public function update(Sale $sale, array $data): Sale;

    public function delete(Sale $sale): bool;

    public function getByDateRange(
        string $startDate,
        string $endDate,
        ?string $status = null,
        ?string $sku = null
    ): Collection;

    public function getReportData(
        string $startDate,
        string $endDate,
        ?string $sku = null
    ): array;

    public function paginate(int $perPage = 15): LengthAwarePaginator;
}
