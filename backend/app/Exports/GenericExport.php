<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GenericExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(
        private string $title,
        private array $headers,
        private Collection|array $rows,
    ) {}

    public function array(): array
    {
        $rows = $this->rows instanceof Collection ? $this->rows : collect($this->rows);

        return $rows->map(fn ($row) => array_values((array) $row))->toArray();
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function title(): string
    {
        return substr($this->title, 0, 31); // Excel sheet name max 31 chars
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E8F0'],
                ],
            ],
        ];
    }
}
