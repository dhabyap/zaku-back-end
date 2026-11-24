<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Traits\ApiResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Export transactions to CSV.
     */
    public function transactionsCsv(Request $request): StreamedResponse
    {
        $user = $request->user();
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $type = $request->query('type'); // income, expense, or all

        $query = $user->transactions()
            ->with('category')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if (!empty($startDate)) {
            $query->whereDate('date', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $query->whereDate('date', '<=', $endDate);
        }
        if (!empty($type) && in_array($type, ['income', 'expense'])) {
            $query->where('type', $type);
        }

        $filename = 'zaku-transactions-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CSV Header
            fputcsv($handle, ['ID', 'Tanggal', 'Tipe', 'Kategori', 'Jumlah (IDR)', 'Deskripsi', 'Sumber']);

            $query->chunk(500, function ($transactions) use ($handle) {
                foreach ($transactions as $tx) {
                    fputcsv($handle, [
                        $tx->id,
                        $tx->date ? $tx->date->format('Y-m-d') : '',
                        $tx->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
                        $tx->category ? $tx->category->name : 'Tanpa Kategori',
                        $tx->amount,
                        $tx->description,
                        $tx->source ?? 'manual',
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}