<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected Collection $rows,
        protected array $headings
    ) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($row): array
    {
        return [
            $row['date'] ?? null,
            $row['branch'] ?? null,
            $row['attendant'] ?? null,
            $row['product'] ?? null,
            $row['liters'] ?? 0,
            $row['event'] ?? null,
        ];
    }
}
