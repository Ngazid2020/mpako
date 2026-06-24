<?php
/**
 * Génère les splash screens PWA BeeZ
 * Usage : php scripts/generate-splash.php
 *
 * Stratégie : resample logo → temp canvas, remplace pixels fond blanc par la
 * couleur gradient correspondante du canvas principal → imagecopy sans alpha.
 * Évite tous les artefacts de canal alpha de GD.
 */

$sizes = [
    [640,  1136],
    [750,  1334],
    [828,  1792],
    [1125, 2436],
    [1242, 2208],
    [1242, 2688],
    [1536, 2048],
    [1668, 2224],
    [1668, 2388],
    [2048, 2732],
];

$logoPath  = __DIR__ . '/../public/images/logo-filament-beez.png';
$outputDir = __DIR__ . '/../public/images/icons';

// Couleurs BeeZ
$bgR = 10; $bgG = 72; $bgB = 72;   // #0a4848 — fond teal

// ── Charger le logo original (fond blanc) ───────────────────────────
$origLogo = imagecreatefrompng($logoPath);
if (! $origLogo) {
    die("Erreur : impossible de charger $logoPath\n");
}
$logoW = imagesx($origLogo);
$logoH = imagesy($origLogo);
echo "Logo chargé : {$logoW}×{$logoH} px\n";

// ── Générer chaque splash ────────────────────────────────────────────
foreach ($sizes as [$w, $h]) {

    // 1. Canvas principal teal
    $canvas = imagecreatetruecolor($w, $h);
    $bgColor = imagecolorallocate($canvas, $bgR, $bgG, $bgB);
    imagefill($canvas, 0, 0, $bgColor);

    // 2. Dégradé radial subtil au centre
    $centerX   = (int)($w / 2);
    $centerY   = (int)($h / 2);
    $maxRadius = (int)(min($w, $h) * 0.55);

    for ($r = $maxRadius; $r > 0; $r -= 2) {
        $ratio = 1 - ($r / $maxRadius);
        $rr = min(255, (int)($bgR + $bgR * 0.6 * $ratio));
        $gg = min(255, (int)($bgG + $bgG * 0.45 * $ratio));
        $bb = min(255, (int)($bgB + $bgB * 0.45 * $ratio));
        $col = imagecolorallocate($canvas, $rr, $gg, $bb);
        imagefilledellipse($canvas, $centerX, $centerY, $r * 2, $r * 2, $col);
        imagecolordeallocate($canvas, $col);
    }

    // 3. Taille cible du logo
    $maxLogoW = (int)($w * 0.55);
    $maxLogoH = (int)($h * 0.32);
    $scale    = min($maxLogoW / $logoW, $maxLogoH / $logoH);
    $dstW     = (int)($logoW * $scale);
    $dstH     = (int)($logoH * $scale);
    $dstX     = (int)(($w - $dstW) / 2);
    $dstY     = (int)(($h - $dstH) / 2) - (int)($h * 0.04);

    // 4. Temp canvas : pré-rempli en blanc pour que les zones transparentes du logo
    //    (fond PNG alpha) apparaissent blanches, et soient ensuite détectées comme
    //    fond et remplacées par le gradient teal.
    $temp = imagecreatetruecolor($dstW, $dstH);
    imagefill($temp, 0, 0, imagecolorallocate($temp, 255, 255, 255));
    imagealphablending($temp, true);
    imagecopyresampled($temp, $origLogo, 0, 0, 0, 0, $dstW, $dstH, $logoW, $logoH);

    // 5. Traitement pixel par pixel :
    //    • Fond blanc/clair → remplacer par la couleur gradient du canvas
    //    • Jaune/chaud (lettre Z, œil) → conserver tel quel
    //    • Tout le reste (teal foncé du texte et de l'abeille) → blanc pur
    //      (le blanc est lisible sur fond teal ; le teal #0a4848 serait invisible)
    $white = 0x00FFFFFF; // blanc opaque pour truecolor
    for ($px = 0; $px < $dstW; $px++) {
        for ($py = 0; $py < $dstH; $py++) {
            $packed = imagecolorat($temp, $px, $py);
            $pr     = ($packed >> 16) & 0xFF;
            $pg     = ($packed >> 8)  & 0xFF;
            $pb     =  $packed        & 0xFF;

            // Fond blanc ou antialiasing clair
            if ($pr >= 200 && $pg >= 200 && $pb >= 200) {
                $canvasX = $dstX + $px;
                $canvasY = $dstY + $py;
                if ($canvasX >= 0 && $canvasX < $w && $canvasY >= 0 && $canvasY < $h) {
                    imagesetpixel($temp, $px, $py, imagecolorat($canvas, $canvasX, $canvasY));
                } else {
                    imagesetpixel($temp, $px, $py, $bgColor);
                }
                continue;
            }

            // Pixel jaune/chaud (Z, œil de l'abeille) : R nettement > B et assez lumineux
            if (($pr - $pb) > 80 && $pr > 150) {
                // conserver tel quel
                continue;
            }

            // Pixel logo teal/sombre → blanc pour être visible sur fond teal
            imagesetpixel($temp, $px, $py, $white);
        }
    }

    // 6. Coller le temp sur le canvas (sans canal alpha, copie directe)
    imagecopy($canvas, $temp, $dstX, $dstY, 0, 0, $dstW, $dstH);
    imagedestroy($temp);

    // 7. Sauvegarder
    $outFile = "$outputDir/splash-{$w}x{$h}.png";
    imagepng($canvas, $outFile, 6);
    imagedestroy($canvas);

    echo "  ✓ splash-{$w}x{$h}.png\n";
}

imagedestroy($origLogo);
echo "\nTerminé — {$outputDir}\n";
