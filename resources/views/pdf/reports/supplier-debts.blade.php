<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 12mm 18mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #1f2937; background: #fff; }

    .header { background: #d97706; color: #fff; padding: 14px 20px; display: table; width: 100%; }
    .header-title { display: table-cell; vertical-align: middle; }
    .header-title h1 { font-size: 16px; font-weight: bold; margin-bottom: 2px; }
    .header-title p  { font-size: 10px; opacity: 0.85; }
    .header-meta { display: table-cell; vertical-align: middle; text-align: right; font-size: 10px; opacity: 0.85; }

    .kpi-row  { display: table; width: 100%; border-collapse: separate; border-spacing: 6px; margin: 12px 0; }
    .kpi-cell { display: table-cell; background: #f3f4f6; border-radius: 6px; padding: 10px 14px; width: 33%; }
    .kpi-label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.03em; }
    .kpi-value { font-size: 18px; font-weight: bold; color: #1f2937; margin-top: 3px; }
    .kpi-cell.amber .kpi-value { color: #b45309; }

    .chart-section { background: #f9fafb; border-radius: 6px; padding: 10px 14px; margin-bottom: 12px; }
    .chart-section img { width: 100%; height: auto; display: block; }
    .section-title { font-size: 10px; font-weight: bold; color: #374151; text-transform: uppercase;
                     letter-spacing: 0.05em; margin-bottom: 8px; }

    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    th { background: #d97706; color: #fff; padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
    td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
    tr:nth-child(even) td { background: #f9fafb; }
    .badge-unpaid  { background: #fee2e2; color: #991b1b; border-radius: 3px; padding: 1px 5px; font-size: 9px; }
    .badge-partial { background: #fef3c7; color: #92400e; border-radius: 3px; padding: 1px 5px; font-size: 9px; }
    .right { text-align: right; }
    .bold  { font-weight: bold; }
    .danger { color: #dc2626; }
</style>
</head>
<body>

<div class="header">
    <div class="header-title">
        <h1>Rapport — Dettes fournisseurs</h1>
        <p>{{ $shop->name }}</p>
    </div>
    <div class="header-meta">Genere le {{ now()->translatedFormat('d F Y a H:i') }}</div>
</div>

<div class="kpi-row">
    <div class="kpi-cell amber">
        <div class="kpi-label">Total du aux fournisseurs</div>
        <div class="kpi-value">{{ number_format($totalDebt, 0, ',', ' ') }} KMF</div>
    </div>
    <div class="kpi-cell">
        <div class="kpi-label">Fournisseurs crediteurs</div>
        <div class="kpi-value">{{ $supplierCount }}</div>
    </div>
    <div class="kpi-cell {{ $purchaseCount > 0 ? 'amber' : '' }}">
        <div class="kpi-label">Achats non soldes</div>
        <div class="kpi-value">{{ $purchaseCount }}</div>
    </div>
</div>

<div class="chart-section">
    <div class="section-title">Top fournisseurs par dette</div>
    @if($chartImg)
        <img src="{{ $chartImg }}" alt="Graphique dettes fournisseurs">
    @else
        <p style="color:#6b7280; font-style:italic; font-size:10px;">Aucune donnee disponible</p>
    @endif
</div>

<div class="section-title" style="margin-bottom:6px">Detail des achats non soldes</div>
<table>
    <thead>
        <tr>
            <th>Reference</th>
            <th>Fournisseur</th>
            <th>Date achat</th>
            <th class="right">Total</th>
            <th class="right">Paye</th>
            <th class="right">Restant du</th>
            <th>Statut</th>
        </tr>
    </thead>
    <tbody>
        @foreach($purchases as $p)
        <tr>
            <td class="bold">{{ $p->reference }}</td>
            <td>{{ $p->supplier?->name ?? '—' }}</td>
            <td>{{ $p->purchased_at?->format('d/m/Y') ?? '—' }}</td>
            <td class="right">{{ number_format($p->total_amount, 0, ',', ' ') }} KMF</td>
            <td class="right">{{ number_format($p->paid_amount, 0, ',', ' ') }} KMF</td>
            <td class="right bold danger">{{ number_format($p->debt_amount, 0, ',', ' ') }} KMF</td>
            <td>
                @if($p->payment_status === 'unpaid')
                    <span class="badge-unpaid">Non paye</span>
                @else
                    <span class="badge-partial">Partiel</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
