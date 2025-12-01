<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;

class SaleController extends Controller
{
    public function __construct(
        protected SaleService $saleService
    ) {}

    public function store(StoreSaleRequest $request): JsonResponse
    {
        $sale = $this->saleService->createSale($request->items);

        return response()->json([
            'message' => 'Sale created and queued for processing',
            'data' => [
                'id' => $sale->id,
                'code' => $sale->code,
                'status' => $sale->status,
                'total_amount' => $sale->total_amount,
            ],
        ], 202);
    }

    public function show(int $id): JsonResponse
    {
        $sale = $this->saleService->getSale($id);

        if (!$sale) {
            return response()->json([
                'message' => 'Sale not found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $sale->id,
                'code' => $sale->code,
                'status' => $sale->status,
                'total_amount' => $sale->total_amount,
                'total_cost' => $sale->total_cost,
                'profit_margin' => $sale->profit_margin,
                'profit_percentage' => $sale->profit_percentage,
                'failure_reason' => $sale->failure_reason,
                'completed_at' => $sale->completed_at,
                'created_at' => $sale->created_at,
                'items' => $sale->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product' => [
                        'id' => $item->product->id,
                        'sku' => $item->product->sku,
                        'name' => $item->product->name,
                    ],
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'profit' => $item->profit,
                ]),
            ],
        ]);
    }
}
