<?php

namespace App\Jobs;

use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSaleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public Sale $sale
    ) {}

    public function handle(SaleService $saleService): void
    {
        Log::info("Processing sale: {$this->sale->code}");

        try {
            $saleService->processSale($this->sale);
            Log::info("Sale processed successfully: {$this->sale->code}");
        } catch (\Exception $e) {
            Log::error("Failed to process sale: {$this->sale->code}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Sale job failed permanently: {$this->sale->code}", [
            'error' => $exception->getMessage(),
        ]);

        $this->sale->update([
            'status' => Sale::STATUS_FAILED,
            'failure_reason' => $exception->getMessage(),
        ]);
    }
}
