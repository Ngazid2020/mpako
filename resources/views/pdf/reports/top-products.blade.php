<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 12mm 18mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #1f2937; background: #fff; }

    .header { background: #7c3aed; color: #fff; padding: 14px 20px; display: table; width: 100%; }
    .header-title { display: table-cell; vertical-align: middle; }
    .header-title h1 { font-size: 16px; font-weight: bold; margin-bottom: 2px; }
    .header-title p  { font-size: 10px; opacity: 0.85; }
    .header-meta { display: table-cell; vertical-align: middle; text-align: right; font-size: 10px; opacity: 0.85; }

    .kpi-row  { display: table; width: 100%; border-collapse: separate; border-spacing: 6px; margin: 12px 0; }
    .kpi-cell { display: table-cell; background: #f3f4f6; border-radius: 6px; padding: 10px 14px; width: 33%; }
    .kpi-label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.03em; }
    .kpi-value { font-size: 18px; font-weight: bold; color: #1f2937; margin-top: 3px; }
    .kpi-cell.violet .kpi-value { color: #6d28d9; }

    .chart-section { background: #f9fafb; border-radius: 6px; padding: 10px 14px; margin-bottom: 12px; }
    .chart-section img { width: 100%; height: auto; display: block; }
    .section-title { font-size: 10px; font-weight: bold; color: #374151; text-transform: uppercase;
                     letter-spacing: 0.05em; margin-bottom: 8px; }

    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    th { background: #7c3aed; color: #fff; padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
    td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
    tr:nth-child(even) td { background: #f9fafb; }
    .right { text-align: right; }
    .bold  { font-weight: bold; }
    .violet { color: #7c3aed; }
    .green  { color: #059669; }
    .red    { color: #dc2626; }
    .rank   { color: #9ca3af; font-family: monospace; font-size: 9px; }
</style>
</head>
<body>

<div class="header">
    <div class="header-title">
        <h1>Rapport — Top produits vendus</h1>
        <p>{{ $shop->name }} · {{ $label }}</p>
    </div>
    <div class="header-meta">Genere le {{ now()->translatedFormat('d F Y a H:i') }}</div>
</div>

<div class="kpi-row">
    <div class="kpi-cell">
        <div class="kpi-label">Produits vendus</div>
        <div class="kpi-value">{{ $productCount }}</div>
    </div>
    <div class="kpi-cell violet">
        <div class="kpi-label">CA genere</div>
        <div class="kpi-value">{{ number_format($totalRevenue, 0, ',', ' ') }} KMF</div>
    </div>
    <div class="kpi-cell">
        <div class="kpi-label">Meilleure vente</div>
        <div class="kpi-value" style="font-size:13px;">{{ $topProduct }}</div>
    </div>
</div>

<div class="chart-section">
    <div class="section-title">Top 10 par chiffre d'affaires</div>
    @if($chartImg)
        <img src="{{ $chartImg }}" alt="Graphique top produits">
    @else
        <p style="color:#6b7280; font-style:italic; font-size:10px;">Aucune donnee disponible</p>
    @endif
</div>

<div class="section-title" style="margin-bottom:6px">Classement complet</div>
<table>
    <thead>
        <tr>
            <th style="width:30px">#</th>
            <th>Produit</th>
            <th>Categorie</th>
            <th class="right">Qte vendue</th>
            <th class="right">CA</th>
            <th class="right">Benefice</th>
            <th class="right">Marge</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $i => $row)
        @php $margin = $row->revenue > 0 ? round($row->profit / $row->revenue * 100, 1) : 0; @endphp
        <tr>
            <td class="rank">{{ $i + 1 }}</td>
            <td class="bold">{{ $row->product_name }}</td>
            <td>{{ $row->category_name ?? '—' }}</td>
            <td class="right">{{ number_format($row->qty_sold, 2, ',', ' ') }} {{ $row->unit }}</td>
            <td class="right violet bold">{{ number_format($row->revenue, 0, ',', ' ') }} KMF</td>
            <td class="right {{ $row->profit >= 0 ? 'green' : 'red' }}">{{ number_format($row->profit, 0, ',', ' ') }} KMF</td>
            <td class="right {{ $margin >= 0 ? 'green' : 'red' }}">{{ $margin }} %</td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
