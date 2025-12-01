<?php

namespace App\Services;

use App\Events\SaleCompleted;
use App\Jobs\ProcessSaleJob;
use App\Models\AuditLog;
use App\Models\InventoryMovement;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\SaleRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        protected SaleRepositoryInterface $saleRepository,
        protected ProductRepositoryInterface $productRepository,
        protected InventoryService $inventoryService
    ) {}

    public function createSale(array $items): Sale
    {
        $sale = DB::transaction(function () use ($items) {
            $sale = $this->saleRepository->create([
                'code' => Sale::generateCode(),
                'status' => Sale::STATUS_PENDING,
                'user_id' => auth()->id(),
            ]);

            foreach ($items as $item) {
                $product = $this->productRepository->find($item['product_id']);

                if (!$product) {
                    throw new \InvalidArgumentException("Product {$item['product_id']} not found");
                }

                if (!$this->inventoryService->checkAvailability($product->id, $item['quantity'])) {
                    throw new \InvalidArgumentException("Insufficient stock for product {$product->sku}");
                }

                $saleItem = new SaleItem([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->sale_price,
                    'unit_cost' => $product->cost_price,
                ]);
                $saleItem->calculateTotals();
                $sale->items()->save($saleItem);
            }

            $sale->load('items');
            $sale->calculateTotals();
            $sale->save();

            AuditLog::log(
                AuditLog::ACTION_CREATE,
                'Sale',
                $sale->id,
                null,
                ['code' => $sale->code, 'total' => $sale->total_amount]
            );

            return $sale;
        });

        ProcessSaleJob::dispatch($sale);

        return $sale;
    }

    public function processSale(Sale $sale): Sale
    {
        return DB::transaction(function () use ($sale) {
            $sale = $this->saleRepository->find($sale->id);

            if (!$sale->isPending()) {
                return $sale;
            }

            $sale->status = Sale::STATUS_PROCESSING;
            $sale->save();

            try {
                foreach ($sale->items as $item) {
                    $this->inventoryService->removeStock(
                        $item->product_id,
                        $item->quantity,
                        InventoryMovement::REFERENCE_SALE,
                        $sale->id
                    );
                }

                $sale->status = Sale::STATUS_COMPLETED;
                $sale->completed_at = now();
                $sale->save();

                event(new SaleCompleted($sale));

                AuditLog::log(
                    AuditLog::ACTION_UPDATE,
                    'Sale',
                    $sale->id,
                    ['status' => Sale::STATUS_PROCESSING],
                    ['status' => Sale::STATUS_COMPLETED]
                );
            } catch (\Exception $e) {
                $sale->status = Sale::STATUS_FAILED;
                $sale->failure_reason = $e->getMessage();
                $sale->save();

                AuditLog::log(
                    AuditLog::ACTION_UPDATE,
                    'Sale',
                    $sale->id,
                    ['status' => Sale::STATUS_PROCESSING],
                    ['status' => Sale::STATUS_FAILED, 'reason' => $e->getMessage()]
                );

                throw $e;
            }

            return $sale;
        });
    }

    public function getSale(int $id): ?Sale
    {
        return $this->saleRepository->findWithItems($id);
    }

    public function getSaleByCode(string $code): ?Sale
    {
        return $this->saleRepository->findByCode($code);
    }

    public function cancelSale(Sale $sale): Sale
    {
        if (!$sale->isPending() && !$sale->isFailed()) {
            throw new \InvalidArgumentException('Only pending or failed sales can be cancelled');
        }

        $sale->status = Sale::STATUS_CANCELLED;
        $sale->save();

        AuditLog::log(
            AuditLog::ACTION_UPDATE,
            'Sale',
            $sale->id,
            ['status' => $sale->getOriginal('status')],
            ['status' => Sale::STATUS_CANCELLED]
        );

        return $sale;
    }
}
