<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    /* Dompdf: ukuran eksplisit (A4 landscape = 297 x 210 mm), tanpa file eksternal */
    @page { margin: 10mm; }
    body { margin: 0; font-family: "DejaVu Sans", sans-serif; color: #1e2433; }
    .page { border: 6px solid #16a34a; height: 178mm; padding: 3mm; }
    .inner { border: 2px solid #f59e0b; height: 170mm; text-align: center; position: relative; }
    .brand { padding-top: 16mm; font-size: 15px; font-weight: bold; color: #16a34a; letter-spacing: 4px; text-transform: uppercase; }
    h1 { font-size: 38px; margin: 10mm 0 3mm; letter-spacing: 2px; }
    .sub { font-size: 14px; color: #667085; }
    .name { font-size: 34px; font-weight: bold; margin: 6mm 0 3mm; color: #15803d; }
    .line { width: 120mm; margin: 0 auto; border-top: 2px solid #e3e7ef; }
    .desc { font-size: 14px; margin: 7mm 30mm 0; line-height: 1.6; }
    .stars { font-size: 26px; color: #f59e0b; margin-top: 6mm; letter-spacing: 6px; }
    .footer { position: absolute; bottom: 8mm; left: 0; right: 0; font-size: 11px; color: #667085; line-height: 1.7; }
    .code { font-family: "DejaVu Sans Mono", monospace; font-size: 14px; color: #1e2433; letter-spacing: 3px; font-weight: bold; }
</style>
</head>
<body>
<div class="page">
    <div class="inner">
        <div class="brand"><?= esc(config('Game')->appName) ?></div>
        <h1>SERTIFIKAT KELULUSAN</h1>
        <div class="sub">diberikan dengan bangga kepada</div>
        <div class="name"><?= esc($name) ?></div>
        <div class="line"></div>
        <div class="desc">
            atas keberhasilan menamatkan seluruh 50 level pembelajaran
            HTML, CSS, JavaScript, SQL, dan PHP dengan nilai sempurna 150 bintang.
        </div>
        <div class="stars">&#9733; &#9733; &#9733;</div>
        <div class="footer">
            Diterbitkan <?= esc(tanggal_id($certificate['issued_at'])) ?><br>
            Kode sertifikat: <span class="code"><?= esc($certificate['code']) ?></span><br>
            Verifikasi keaslian: <?= esc($verifyUrl) ?>
        </div>
    </div>
</div>
</body>
</html>
