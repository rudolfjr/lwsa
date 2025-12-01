<?php

namespace App\Repositories\Eloquent;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Repositories\Contracts\SaleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SaleRepository implements SaleRepositoryInterface
{
    public function __construct(
        protected Sale $model
    ) {}

    public function find(int $id): ?Sale
    {
        return $this->model->find($id);
    }

    public function findWithItems(int $id): ?Sale
    {
        return $this->model->with(['items.product', 'user'])->find($id);
    }

    public function findByCode(string $code): ?Sale
    {
        return $this->model->where('code', $code)->first();
    }

    public function create(array $data): Sale
    {
        return $this->model->create($data);
    }

    public function update(Sale $sale, array $data): Sale
    {
        $sale->update($data);
        return $sale->fresh();
    }

    public function delete(Sale $sale): bool
    {
        return $sale->delete();
    }

    public function getByDateRange(
        string $startDate,
        string $endDate,
        ?string $status = null,
        ?string $sku = null
    ): Collection {
        $query = $this->model
            ->with(['items.product'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($status) {
            $query->where('status', $status);
        }

        if ($sku) {
            $query->whereHas('items.product', function ($q) use ($sku) {
                $q->where('sku', $sku);
            });
        }

        return $query->get();
    }

    public function getReportData(
        string $startDate,
        string $endDate,
        ?string $sku = null
    ): array {
        $query = DB::table('sales')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->whereNull('sales.deleted_at')
            ->whereNull('sale_items.deleted_at');

        if ($sku) {
            $query->where('products.sku', $sku);
        }

        $summary = (clone $query)->selectRaw('
            COUNT(DISTINCT sales.id) as total_sales,
            SUM(sale_items.quantity) as total_quantity,
            SUM(sale_items.subtotal) as total_amount,
            SUM(sale_items.profit) as total_profit
        ')->first();

        $byProduct = (clone $query)->selectRaw('
            products.sku,
            products.name,
            SUM(sale_items.quantity) as quantity_sold,
            SUM(sale_items.subtotal) as total_amount,
            SUM(sale_items.profit) as total_profit
        ')
            ->groupBy('products.id', 'products.sku', 'products.name')
            ->orderByDesc('total_amount')
            ->get();

        return [
            'summary' => $summary,
            'by_product' => $byProduct,
        ];
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
