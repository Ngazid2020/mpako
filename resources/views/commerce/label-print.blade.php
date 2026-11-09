<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Étiquettes — {{ $shop->name }}</title>
    <style>
        /* ── Reset ──────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f3f4f6; color: #111; }

        /* ── Barre de contrôles (masquée à l'impression) ── */
        .controls {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #1e293b;
            color: #f1f5f9;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .controls h1 { font-size: 14px; font-weight: 600; opacity: .8; }
        .controls .group { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .controls label { font-size: 13px; }
        .controls input[type="number"] {
            width: 64px;
            padding: 5px 8px;
            border-radius: 6px;
            border: 1px solid #475569;
            background: #334155;
            color: #f1f5f9;
            font-size: 14px;
            text-align: center;
        }
        .btn {
            padding: 7px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: opacity .15s;
        }
        .btn:hover { opacity: .85; }
        .btn-apply  { background: #3b82f6; color: #fff; }
        .btn-print  { background: #22c55e; color: #fff; }
        .btn-close  { background: #475569; color: #e2e8f0; }

        /* ── Grille d'étiquettes ─────────────────── */
        #labels-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6mm;
            padding: 8mm;
            justify-content: flex-start;
        }

        /* ── Étiquette individuelle ──────────────── */
        .label {
            width: 90mm;
            min-height: 52mm;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 4mm 5mm 3mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .label-shop {
            font-size: 8px;
            color: #9ca3af;
            letter-spacing: .05em;
            text-transform: uppercase;
            align-self: flex-start;
            margin-bottom: 2mm;
        }
        .label-name {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            text-align: center;
            line-height: 1.3;
            margin-bottom: 3mm;
            word-break: break-word;
        }
        .label-barcode svg {
            max-width: 100%;
            height: auto;
            display: block;
        }
        .label-price {
            font-size: 18px;
            font-weight: 800;
            color: #2563eb;
            margin-top: 3mm;
            letter-spacing: .03em;
        }
        .label-price span {
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            margin-left: 2px;
        }

        /* ── Impression ──────────────────────────── */
        @media print {
            .controls { display: none !important; }
            body { background: #fff; }
            #labels-grid {
                gap: 4mm;
                padding: 0;
            }
            .label {
                border-color: #9ca3af;
                box-shadow: none;
            }
        }

        @page {
            size: A4;
            margin: 10mm;
        }

        /* ── Message vide ────────────────────────── */
        #empty-msg {
            padding: 40px;
            text-align: center;
            color: #6b7280;
            font-size: 15px;
            display: none;
        }
    </style>
</head>
<body>

    {{-- Barre de contrôles --}}
    <div class="controls">
        <h1>🏷️ Étiquettes produits — {{ $shop->name }}</h1>
        <div class="group">
            <label for="copies">Copies par étiquette :</label>
            <input type="number" id="copies" value="1" min="1" max="99">
            <button class="btn btn-apply" onclick="buildLabels()">Appliquer</button>
        </div>
        <div class="group">
            <button class="btn btn-print" onclick="window.print()">🖨️ Imprimer</button>
            <button class="btn btn-close" onclick="window.close()">Fermer</button>
        </div>
    </div>

    <div id="empty-msg">Aucun produit à afficher.</div>
    <div id="labels-grid"></div>

    {{-- JsBarcode via CDN (fenêtre standalone = pas de CSP Filament) --}}
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

    <script>
        const SHOP_NAME = @json($shop->name);

        const PRODUCTS = @json($products->map(fn ($p) => [
            'id'      => $p->id,
            'name'    => $p->name,
            'price'   => number_format($p->sell_price, 0, ',', ' '),
            'barcode' => $p->barcode ?: ('P' . str_pad($p->id, 7, '0', STR_PAD_LEFT)),
        ]));

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function buildLabels() {
            const copies = Math.max(1, Math.min(99, parseInt(document.getElementById('copies').value) || 1));
            const grid   = document.getElementById('labels-grid');
            const empty  = document.getElementById('empty-msg');

            grid.innerHTML = '';

            if (!PRODUCTS.length) {
                empty.style.display = 'block';
                return;
            }

            PRODUCTS.forEach(function (product) {
                for (var i = 0; i < copies; i++) {
                    var div = document.createElement('div');
                    div.className = 'label';
                    div.innerHTML =
                        '<div class="label-shop">' + escapeHtml(SHOP_NAME) + '</div>' +
                        '<div class="label-name">' + escapeHtml(product.name) + '</div>' +
                        '<div class="label-barcode"><svg id="bc-' + product.id + '-' + i + '"></svg></div>' +
                        '<div class="label-price">' + escapeHtml(product.price) + '<span>KMF</span></div>';
                    grid.appendChild(div);

                    try {
                        JsBarcode(
                            div.querySelector('svg'),
                            product.barcode,
                            {
                                format:       'CODE128',
                                lineColor:    '#111',
                                width:        1.6,
                                height:       45,
                                displayValue: true,
                                fontSize:     11,
                                margin:       4,
                                background:   '#ffffff',
                            }
                        );
                    } catch (e) {
                        div.querySelector('.label-barcode').innerHTML =
                            '<div style="font-size:10px;color:#ef4444;padding:4px">Barcode invalide</div>';
                    }
                }
            });
        }

        // Construire au chargement
        buildLabels();

        // Mettre à jour au changement du champ copies
        document.getElementById('copies').addEventListener('change', buildLabels);
    </script>
</body>
</html>
