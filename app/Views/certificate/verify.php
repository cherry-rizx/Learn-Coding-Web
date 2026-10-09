<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Verifikasi Sertifikat<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container container-narrow">
    <div class="card center">
        <?php if ($found !== null): ?>
            <div class="feature-icon" style="margin:0 auto 12px;width:64px;height:64px;border-radius:50%;display:grid;place-items:center;background:var(--primary-soft);color:var(--primary)"><?= icon('shield-check') ?></div>
            <h1>Sertifikat asli</h1>
            <p>Sertifikat dengan kode <span class="cert-code"><?= esc($found['code']) ?></span> terdaftar atas nama:</p>
            <h2><?= esc($found['username']) ?></h2>
            <p class="muted">Diterbitkan <?= esc(tanggal_id($found['issued_at'])) ?> oleh <?= esc(config('Game')->appName) ?> setelah menamatkan 50 level dengan 150 bintang.</p>
        <?php else: ?>
            <div style="margin:0 auto 12px;width:64px;height:64px;border-radius:50%;display:grid;place-items:center;background:var(--danger-soft);color:var(--danger)"><?= icon('x') ?></div>
            <h1>Sertifikat tidak ditemukan</h1>
            <p class="muted">Kode <span class="cert-code"><?= esc($code) ?></span> tidak terdaftar. Periksa kembali kode pada sertifikat.</p>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
