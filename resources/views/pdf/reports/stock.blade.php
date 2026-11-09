<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    @page { margin: 12mm 18mm; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1f2937; background: #fff; }

    .header { background: #1d4ed8; color: #fff; padding: 14px 20px; }
    .header h1 { font-size: 17px; font-weight: bold; margin-bottom: 3px; }
    .header-sub { font-size: 10px; opacity: 0.85; display: table; width: 100%; }
    .header-shop { display: table-cell; }
    .header-date { display: table-cell; text-align: right; }

    .kpi-row { display: table; width: 100%; margin: 12px 0; border-collapse: separate; border-spacing: 8px; }
    .kpi-cell { display: table-cell; background: #f3f4f6; border-radius: 6px; padding: 10px 14px; width: 33%; }
    .kpi-label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }
    .kpi-value { font-size: 20px; font-weight: bold; color: #1f2937; margin-top: 4px; }
    .kpi-cell.alert { background: #fef2f2; }
    .kpi-cell.alert .kpi-value { color: #dc2626; }

    .section-title { font-size: 9px; font-weight: bold; color: #374151; text-transform: uppercase;
                     letter-spacing: 0.06em; margin: 12px 0 6px; }

    .chart-box { background: #f9fafb; border-radius: 6px; padding: 10px 12px; margin-bottom: 12px; }
    .chart-box img { width: 100%; height: auto; display: block; }

    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    th { background: #1d4ed8; color: #fff; padding: 6px 8px; text-align: left;
         font-size: 9px; text-transform: uppercase; letter-spacing: 0.04em; }
    td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
    tr:nth-child(even) td { background: #f9fafb; }
    .right { text-align: right; }
    .bold  { font-weight: bold; }
    .badge-ok    { background: #d1fae5; color: #065f46; border-radius: 3px; padding: 1px 5px; font-size: 9px; }
    .badge-alert { background: #fee2e2; color: #991b1b; border-radius: 3px; padding: 1px 5px; font-size: 9px; }
</style>
</head>
<body>

<div class="header">
    <h1>Rapport — Etat du stock</h1>
    <div class="header-sub">
        <span class="header-shop">{{ $shop->name }}</span>
        <span class="header-date">Genere le {{ now()->translatedFormat('d F Y a H:i') }}</span>
    </div>
</div>

<div class="kpi-row">
    <div class="kpi-cell">
        <div class="kpi-label">Valeur totale du stock</div>
        <div class="kpi-value">{{ number_format($totalValue, 0, ',', ' ') }} KMF</div>
    </div>
    <div class="kpi-cell">
        <div class="kpi-label">Produits actifs</div>
        <div class="kpi-value">{{ $totalProducts }}</div>
    </div>
    <div class="kpi-cell {{ $alertCount > 0 ? 'alert' : '' }}">
        <div class="kpi-label">Produits en alerte stock</div>
        <div class="kpi-value">{{ $alertCount }}</div>
    </div>
</div>

<div class="section-title">Top produits par valeur de stock</div>
<div class="chart-box">
    @if($chartImg)
        <img src="{{ $chartImg }}" alt="Graphique stock">
    @else
        <p style="color:#6b7280; font-style:italic; font-size:10px;">Aucune donnee disponible</p>
    @endif
</div>

<div class="section-title">Detail des produits</div>
<table>
    <thead>
        <tr>
            <th>Produit</th>
            <th>Categorie</th>
            <th>Stock</th>
            <th class="right">PA/unite</th>
            <th class="right">PV/unite</th>
            <th class="right">Valeur stock</th>
            <th>Statut</th>
        </tr>
    </thead>
    <tbody>
        @foreach($products as $p)
        <tr>
            <td class="bold">{{ $p->name }}</td>
            <td>{{ $p->category?->name ?? '—' }}</td>
            <td>{{ number_format($p->stock_qty, 2, ',', ' ') }} {{ $p->unit?->abbreviation }}</td>
            <td class="right">{{ number_format($p->buy_price, 0, ',', ' ') }} KMF</td>
            <td class="right">{{ number_format($p->sell_price, 0, ',', ' ') }} KMF</td>
            <td class="right bold">{{ number_format($p->stock_qty * $p->buy_price, 0, ',', ' ') }} KMF</td>
            <td>
                @if($p->stock_qty <= ($p->stock_alert ?? 0))
                    <span class="badge-alert">Alerte</span>
                @else
                    <span class="badge-ok">OK</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
