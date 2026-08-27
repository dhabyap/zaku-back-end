<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Traits\ApiResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    use ApiResponse;

    /**
     * Generate PDF for monthly recap.
     */
    public function monthlyPdf(Request $request, DashboardService $dashboardService): Response
    {
        $month = $request->query('month', null);
        $year = $request->query('year', null);

        // Default to previous month if no month/year is provided
        if (empty($month) || empty($year)) {
            $prevMonth = now()->subMonth();
            $month = $prevMonth->month;
            $year = $prevMonth->year;
        }

        // Get the same data as the monthlyRecap endpoint
        $data = $dashboardService->getMonthlyRecap($request->user(), (int) $month, (int) $year);

        // Load the PDF view and pass the data
        $pdf = Pdf::loadView('exports.monthly_recap', $data);

        // Set PDF options (optional)
        $pdf->setPaper('a4', 'portrait');

        // Return the PDF as a download
        return $pdf->download("zaku-recap-{$year}-{$month}.pdf");
    }
}