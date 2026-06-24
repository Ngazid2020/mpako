<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    @page { margin: 12mm 18mm; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #1f2937; background: #fff; }

    .header { background: #0f766e; color: #fff; padding: 14px 20px; display: table; width: 100%; }
    .header-title { display: table-cell; vertical-align: middle; }
    .header-title h1 { font-size: 16px; font-weight: bold; margin-bottom: 2px; }
    .header-title p  { font-size: 10px; opacity: 0.85; }
    .header-meta { display: table-cell; vertical-align: middle; text-align: right; font-size: 10px; opacity: 0.85; }

    .kpi-row  { display: table; width: 100%; border-collapse: separate; border-spacing: 6px; margin: 12px 0; }
    .kpi-cell { display: table-cell; background: #f3f4f6; border-radius: 6px; padding: 10px 14px; width: 33%; }
    .kpi-label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.03em; }
    .kpi-value { font-size: 18px; font-weight: bold; color: #1f2937; margin-top: 3px; }

    .chart-section { background: #f9fafb; border-radius: 6px; padding: 10px 14px; margin-bottom: 12px; }
    .chart-section img { width: 100%; height: auto; display: block; }
    .section-title { font-size: 10px; font-weight: bold; color: #374151; text-transform: uppercase;
                     letter-spacing: 0.05em; margin-bottom: 8px; }

    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    th { background: #0f766e; color: #fff; padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.03em; }
    td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
    tr:nth-child(even) td { background: #f9fafb; }
    .badge-ok     { background: #d1fae5; color: #065f46; border-radius: 3px; padding: 1px 5px; font-size: 9px; }
    .badge-cancel { background: #fee2e2; color: #991b1b; border-radius: 3px; padding: 1px 5px; font-size: 9px; }
    .right { text-align: right; }
    .bold  { font-weight: bold; }
</style>
</head>
<body>

<div class="header">
    <div class="header-title">
        <h1>Rapport — Ventes</h1>
        <p>{{ $shop->name }} · {{ $label }}</p>
    </div>
    <div class="header-meta">
        Genere le {{ now()->translatedFormat('d F Y a H:i') }}
    </div>
</div>

<div class="kpi-row">
    <div class="kpi-cell">
        <div class="kpi-label">Chiffre d'affaires</div>
        <div class="kpi-value">{{ number_format($total, 0, ',', ' ') }} KMF</div>
    </div>
    <div class="kpi-cell">
        <div class="kpi-label">Ventes validees</div>
        <div class="kpi-value">{{ $count }}</div>
    </div>
    <div class="kpi-cell">
        <div class="kpi-label">Ticket moyen</div>
        <div class="kpi-value">{{ number_format($avg, 0, ',', ' ') }} KMF</div>
    </div>
</div>

<div class="chart-section">
    <div class="section-title">Evolution des ventes sur la periode</div>
    @if($chartImg)
        <img src="{{ $chartImg }}" alt="Graphique ventes">
    @else
        <p style="color:#6b7280; font-style:italic; font-size:10px;">Aucune donnee disponible</p>
    @endif
</div>

<div class="section-title" style="margin-bottom:6px">Detail des ventes</div>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Reference</th>
            <th class="right">Articles</th>
            <th class="right">Total</th>
            <th>Statut</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sales as $s)
        <tr>
            <td>{{ $s->created_at->format('d/m/Y H:i') }}</td>
            <td class="bold">{{ $s->reference }}</td>
            <td class="right">{{ $s->items_count }}</td>
            <td class="right bold">{{ number_format($s->total_amount, 0, ',', ' ') }} KMF</td>
            <td>
                @if($s->status === 'completed')
                    <span class="badge-ok">Validee</span>
                @else
                    <span class="badge-cancel">Annulee</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
