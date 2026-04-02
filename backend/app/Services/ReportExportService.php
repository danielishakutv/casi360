<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    /**
     * Export data in the requested format.
     *
     * @param  string            $title   Report title (used for filename + PDF header)
     * @param  array             $headers Column headers
     * @param  Collection|array  $rows    Each row is an associative array matching $headers order
     * @param  string            $format  csv | excel | pdf
     * @param  array             $meta    Optional key-value metadata shown on PDF cover
     * @return StreamedResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(string $title, array $headers, Collection|array $rows, string $format, array $meta = [])
    {
        $filename = Str::slug($title) . '-' . now()->format('Y-m-d-His');

        return match ($format) {
            'csv'   => $this->toCsv($filename, $headers, $rows),
            'excel' => $this->toExcel($filename, $title, $headers, $rows),
            'pdf'   => $this->toPdf($filename, $title, $headers, $rows, $meta),
            default => throw new \InvalidArgumentException("Unsupported export format: {$format}"),
        };
    }

    /**
     * CSV — streamed, zero-dependency.
     */
    private function toCsv(string $filename, array $headers, Collection|array $rows): StreamedResponse
    {
        return new StreamedResponse(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens with correct encoding
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, array_values((array) $row));
            }

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            'Cache-Control'       => 'no-store',
        ]);
    }

    /**
     * Excel — via maatwebsite/excel.
     */
    private function toExcel(string $filename, string $title, array $headers, Collection|array $rows)
    {
        $export = new \App\Exports\GenericExport($title, $headers, $rows);

        return Excel::download($export, "{$filename}.xlsx");
    }

    /**
     * PDF — via barryvdh/laravel-dompdf with Blade view.
     */
    private function toPdf(string $filename, string $title, array $headers, Collection|array $rows, array $meta)
    {
        $rows = $rows instanceof Collection ? $rows : collect($rows);

        $pdf = Pdf::loadView('reports.table', compact('title', 'headers', 'rows', 'meta'))
            ->setPaper('a4', count($headers) > 6 ? 'landscape' : 'portrait');

        return $pdf->download("{$filename}.pdf");
    }
}
