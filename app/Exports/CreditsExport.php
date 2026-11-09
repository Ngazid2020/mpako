<?php

namespace App\Exports;

use App\Models\Shop;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CreditsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(private Shop $shop) {}

    public function collection()
    {
        return $this->shop->credits()
            ->with(['customer'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function headings(): array
    {
        return ['Référence', 'Date', 'Client', 'Statut', 'Total (KMF)', 'Payé (KMF)', 'Reste (KMF)', 'Échéance', 'En retard'];
    }

    public function map($credit): array
    {
        $statusLabels = ['pending' => 'En attente', 'partial' => 'Partiel', 'paid' => 'Soldé'];

        return [
            $credit->reference,
            $credit->created_at->format('d/m/Y'),
            $credit->customer?->name ?? '—',
            $statusLabels[$credit->status] ?? $credit->status,
            (int) $credit->total_amount,
            (int) $credit->paid_amount,
            (int) $credit->remaining_amount,
            $credit->due_date ? \Carbon\Carbon::parse($credit->due_date)->format('d/m/Y') : '—',
            ($credit->due_date && $credit->status !== 'paid' && now()->isAfter($credit->due_date)) ? 'Oui' : 'Non',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
