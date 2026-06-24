<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 12mm 18mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #1f2937; background: #fff; }

    .header { background: #059669; color: #fff; padding: 14px 20px; display: table; width: 100%; }
    .header-title { display: table-cell; vertical-align: middle; }
    .header-title h1 { font-size: 16px; font-weight: bold; margin-bottom: 2px; }
    .header-title p  { font-size: 10px; opacity: 0.85; }
    .header-meta { display: table-cell; vertical-align: middle; text-align: right; font-size: 10px; opacity: 0.85; }

    .kpi-row  { display: table; width: 100%; border-collapse: separate; border-spacing: 5px; margin: 12px 0; }
    .kpi-cell { display: table-cell; background: #f3f4f6; border-radius: 6px; padding: 9px 12px; width: 16.6%; }
    .kpi-label { font-size: 8px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.03em; }
    .kpi-value { font-size: 15px; font-weight: bold; color: #1f2937; margin-top: 3px; }
    .kpi-cell.green  { background: #d1fae5; } .kpi-cell.green  .kpi-value { color: #065f46; }
    .kpi-cell.red    { background: #fee2e2; } .kpi-cell.red    .kpi-value { color: #991b1b; }
    .kpi-cell.amber  { background: #fef3c7; } .kpi-cell.amber  .kpi-value { color: #92400e; }
    .kpi-cell.blue   { background: #dbeafe; } .kpi-cell.blue   .kpi-value { color: #1e40af; }

    .chart-section { background: #f9fafb; border-radius: 6px; padding: 10px 14px; margin-bottom: 12px; }
    .chart-section img { width: 100%; height: auto; display: block; }
    .section-title { font-size: 10px; font-weight: bold; color: #374151; text-transform: uppercase;
                     letter-spacing: 0.05em; margin-bottom: 8px; }

    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    th { background: #059669; color: #fff; padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
    td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
    tr:nth-child(even) td { background: #f9fafb; }
    .right { text-align: right; }
    .bold  { font-weight: bold; }
    .danger { color: #dc2626; }
</style>
</head>
<body>

<div class="header">
    <div class="header-title">
        <h1>Rapport — Resultat net</h1>
        <p>{{ $shop->name }} · {{ $label }}</p>
    </div>
    <div class="header-meta">Genere le {{ now()->translatedFormat('d F Y a H:i') }}</div>
</div>

<div class="kpi-row">
    <div class="kpi-cell green">
        <div class="kpi-label">Chiffre d'affaires</div>
        <div class="kpi-value">{{ number_format($revenue, 0, ',', ' ') }} KMF</div>
    </div>
    <div class="kpi-cell red">
        <div class="kpi-label">Cout achats</div>
        <div class="kpi-value">{{ number_format($purchaseCost, 0, ',', ' ') }} KMF</div>
    </div>
    <div class="kpi-cell amber">
        <div class="kpi-label">Depenses</div>
        <div class="kpi-value">{{ number_format($expenses, 0, ',', ' ') }} KMF</div>
    </div>
    <div class="kpi-cell {{ $grossProfit >= 0 ? 'green' : 'red' }}">
        <div class="kpi-label">Benefice brut</div>
        <div class="kpi-value">{{ number_format($grossProfit, 0, ',', ' ') }} KMF</div>
    </div>
    <div class="kpi-cell {{ $netProfit >= 0 ? 'green' : 'red' }}">
        <div class="kpi-label">Resultat net</div>
        <div class="kpi-value">{{ number_format($netProfit, 0, ',', ' ') }} KMF</div>
    </div>
    <div class="kpi-cell blue">
        <div class="kpi-label">Marge brute</div>
        <div class="kpi-value">{{ $margin }} %</div>
    </div>
</div>

<div class="chart-section">
    <div class="section-title">Synthese financiere</div>
    @if($chartImg)
        <img src="{{ $chartImg }}" alt="Graphique résultat">
    @else
        <p style="color:#6b7280; font-style:italic; font-size:10px;">Aucune donnee disponible</p>
    @endif
</div>

<div class="section-title" style="margin-bottom:6px">Detail des depenses</div>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Categorie</th>
            <th>Description</th>
            <th class="right">Montant</th>
        </tr>
    </thead>
    <tbody>
        @foreach($expenses_list as $e)
        <tr>
            <td>{{ $e->spent_at->format('d/m/Y') }}</td>
            <td>{{ $e->category?->name ?? '—' }}</td>
            <td>{{ $e->description ?? '—' }}</td>
            <td class="right danger bold">{{ number_format($e->amount, 0, ',', ' ') }} KMF</td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
