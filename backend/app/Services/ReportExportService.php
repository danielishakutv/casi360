<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    /**
     * Export data in the requested format (CSV or PDF).
     *
     * @param  string            $title   Report title (used for filename + PDF header)
     * @param  array             $headers Column headers
     * @param  Collection|array  $rows    Each row is an associative array matching $headers order
     * @param  string            $format  csv | pdf
     * @param  array             $meta    Optional key-value metadata shown on the PDF
     * @return StreamedResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function export(string $title, array $headers, Collection|array $rows, string $format, array $meta = [])
    {
        $filename = Str::slug($title) . '-' . now()->format('Y-m-d-His');

        return match ($format) {
            'pdf'   => $this->toPdf($filename, $title, $headers, $rows, $meta),
            default => $this->toCsv($filename, $headers, $rows), // csv (and any legacy excel request) → CSV
        };
    }

    /**
     * CSV — streamed, zero-dependency.
     */
    private function toCsv(string $filename, array $headers, Collection|array $rows): StreamedResponse
    {
        return new StreamedResponse(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel/Sheets open with correct encoding
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
     * PDF — via barryvdh/laravel-dompdf, rendered from a self-contained HTML
     * string (no Blade view file required, so it works regardless of whether
     * resources/views was synced to the server).
     */
    private function toPdf(string $filename, string $title, array $headers, Collection|array $rows, array $meta)
    {
        $rows = $rows instanceof Collection ? $rows : collect($rows);

        $pdf = Pdf::loadHTML($this->buildPdfHtml($title, $headers, $rows, $meta))
            ->setPaper('a4', count($headers) > 6 ? 'landscape' : 'portrait');

        return $pdf->download("{$filename}.pdf");
    }

    /**
     * Build a clean, branded HTML document for the PDF.
     */
    private function buildPdfHtml(string $title, array $headers, Collection $rows, array $meta): string
    {
        $esc = static fn ($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

        $titleEsc  = $esc($title);
        $generated = now()->format('d M Y, H:i');
        $count     = $rows->count();

        $headerCells = '';
        foreach ($headers as $h) {
            $headerCells .= '<th>' . $esc($h) . '</th>';
        }

        if ($count === 0) {
            $bodyRows = '<tr><td class="empty" colspan="' . count($headers) . '">No records found</td></tr>';
        } else {
            $bodyRows = '';
            foreach ($rows as $row) {
                $bodyRows .= '<tr>';
                foreach (array_values((array) $row) as $cell) {
                    $bodyRows .= '<td>' . $esc($cell) . '</td>';
                }
                $bodyRows .= '</tr>';
            }
        }

        $metaHtml = '';
        if (!empty($meta)) {
            $metaRows = '';
            foreach ($meta as $k => $v) {
                $metaRows .= '<tr><td class="ml">' . $esc($k) . ':</td><td>' . $esc($v) . '</td></tr>';
            }
            $metaHtml = '<table class="meta">' . $metaRows . '</table>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Helvetica','Arial',sans-serif; font-size:10px; color:#1f2937; }
  .brandbar { background:#0e7c5a; color:#ffffff; padding:16px 20px; }
  .brandbar .org { font-size:10px; letter-spacing:1px; text-transform:uppercase; color:#d1fae5; }
  .brandbar h1 { font-size:18px; margin-top:3px; }
  .brandbar .gen { font-size:9px; color:#d1fae5; margin-top:3px; }
  .content { padding:14px 20px 0; }
  table.meta { margin:0 0 12px; }
  table.meta td { padding:2px 12px 2px 0; font-size:9px; color:#4b5563; }
  table.meta td.ml { font-weight:bold; color:#111827; }
  table.data { width:100%; border-collapse:collapse; margin-top:4px; }
  table.data thead th { background:#0e7c5a; color:#ffffff; font-size:9px; font-weight:bold; text-align:left; padding:7px 8px; }
  table.data tbody td { padding:6px 8px; font-size:9px; border-bottom:1px solid #e5e7eb; vertical-align:top; word-wrap:break-word; }
  table.data tbody tr:nth-child(even) { background:#f3faf7; }
  td.empty { text-align:center; padding:24px; color:#9ca3af; }
  .footer { margin:18px 20px 0; padding-top:8px; border-top:1px solid #e5e7eb; font-size:8px; color:#9ca3af; }
  .footer .right { float:right; }
</style></head><body>
  <div class="brandbar">
    <div class="org">CASI 360 &mdash; Care Aid Support Initiative</div>
    <h1>{$titleEsc}</h1>
    <div class="gen">Generated {$generated}</div>
  </div>
  <div class="content">
    {$metaHtml}
    <table class="data">
      <thead><tr>{$headerCells}</tr></thead>
      <tbody>{$bodyRows}</tbody>
    </table>
  </div>
  <div class="footer">
    <span>{$titleEsc}</span>
    <span class="right">{$count} record(s) &bull; CASI 360</span>
  </div>
</body></html>
HTML;
    }
}
