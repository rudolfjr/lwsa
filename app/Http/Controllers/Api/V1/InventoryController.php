<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryRequest;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->inventoryService->getInventorySummary();

        return response()->json([
            'data' => $data,
        ]);
    }

    public function store(StoreInventoryRequest $request): JsonResponse
    {
        $inventory = $this->inventoryService->addStock($request->validated());

        return response()->json([
            'message' => 'Stock added successfully',
            'data' => $inventory,
        ], 201);
    }
}
