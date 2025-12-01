<?php

namespace App\Services;

use App\Repositories\Contracts\SaleRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class ReportService
{
    private const CACHE_PREFIX = 'report:sales:';
    private const CACHE_TTL = 300;

    public function __construct(
        protected SaleRepositoryInterface $saleRepository
    ) {}

    public function getSalesReport(
        string $startDate,
        string $endDate,
        ?string $sku = null
    ): array {
        $cacheKey = $this->generateCacheKey($startDate, $endDate, $sku);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate, $sku) {
            $data = $this->saleRepository->getReportData($startDate, $endDate, $sku);

            return [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'filters' => [
                    'sku' => $sku,
                ],
                'summary' => [
                    'total_sales' => (int) ($data['summary']->total_sales ?? 0),
                    'total_quantity' => (int) ($data['summary']->total_quantity ?? 0),
                    'total_amount' => (float) ($data['summary']->total_amount ?? 0),
                    'total_profit' => (float) ($data['summary']->total_profit ?? 0),
                ],
                'by_product' => $data['by_product']->map(function ($item) {
                    return [
                        'sku' => $item->sku,
                        'name' => $item->name,
                        'quantity_sold' => (int) $item->quantity_sold,
                        'total_amount' => (float) $item->total_amount,
                        'total_profit' => (float) $item->total_profit,
                    ];
                }),
            ];
        });
    }

    private function generateCacheKey(string $startDate, string $endDate, ?string $sku): string
    {
        return self::CACHE_PREFIX . md5("{$startDate}:{$endDate}:{$sku}");
    }

    public function clearCache(): void
    {
        Cache::flush();
    }
}
