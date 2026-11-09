<?php

namespace App\Exports;

use App\Models\Shop;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(private Shop $shop) {}

    public function collection()
    {
        return $this->shop->sales()
            ->with(['customer', 'items'])
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->get();
    }

    public function headings(): array
    {
        return ['Référence', 'Date', 'Client', 'Type', 'Total (KMF)', 'Payé (KMF)', 'Monnaie (KMF)', 'Note'];
    }

    public function map($sale): array
    {
        return [
            $sale->reference,
            $sale->created_at->format('d/m/Y H:i'),
            $sale->customer?->name ?? 'Comptant',
            $sale->payment_type === 'credit' ? 'Crédit' : 'Comptant',
            (int) $sale->total_amount,
            (int) $sale->paid_amount,
            (int) $sale->change_amount,
            $sale->note ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
