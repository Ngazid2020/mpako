<?php

namespace App\Exports;

use App\Models\Shop;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchasesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(private Shop $shop) {}

    public function collection()
    {
        return $this->shop->purchases()
            ->with(['supplier'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function headings(): array
    {
        return ['Référence', 'Date', 'Fournisseur', 'Statut', 'Paiement', 'Total (KMF)', 'Payé (KMF)', 'Reste (KMF)'];
    }

    public function map($purchase): array
    {
        $statusLabels = ['pending' => 'En attente', 'completed' => 'Complété', 'cancelled' => 'Annulé'];
        $payLabels    = ['unpaid' => 'Impayé', 'partial' => 'Partiel', 'paid' => 'Payé'];

        return [
            $purchase->reference,
            $purchase->created_at->format('d/m/Y H:i'),
            $purchase->supplier?->name ?? '—',
            $statusLabels[$purchase->status] ?? $purchase->status,
            $payLabels[$purchase->payment_status] ?? $purchase->payment_status,
            (int) $purchase->total_amount,
            (int) $purchase->paid_amount,
            (int) ($purchase->total_amount - $purchase->paid_amount),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
