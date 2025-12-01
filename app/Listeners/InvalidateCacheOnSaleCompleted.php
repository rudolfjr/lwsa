<?php

namespace App\Listeners;

use App\Events\SaleCompleted;
use App\Services\InventoryService;
use App\Services\ReportService;

class InvalidateCacheOnSaleCompleted
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected ReportService $reportService
    ) {}

    public function handle(SaleCompleted $event): void
    {
        $this->inventoryService->clearCache();
        $this->reportService->clearCache();
    }
}
