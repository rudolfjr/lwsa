<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesReportRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function sales(SalesReportRequest $request): JsonResponse
    {
        $report = $this->reportService->getSalesReport(
            $request->start_date,
            $request->end_date,
            $request->sku
        );

        return response()->json([
            'data' => $report,
        ]);
    }
}
