<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Sertifikat<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container container-narrow">
    <h1><?= icon('award') ?> Sertifikat</h1>

    <?php if ($certificate !== null): ?>
        <div class="cert-preview">
            <p class="badge badge-ok"><?= icon('shield-check') ?> Terverifikasi</p>
            <h2>Sertifikat Kelulusan</h2>
            <p class="muted">diberikan kepada</p>
            <h2><?= esc(display_name($currentUser)) ?></h2>
            <p>telah menamatkan 50 level <?= esc(config('Game')->appName) ?> (HTML, CSS, JavaScript, SQL, PHP) dengan <?= (int) $target ?> bintang penuh.</p>
            <p class="muted">Diterbitkan <?= esc(tanggal_id($certificate['issued_at'])) ?></p>
            <p class="cert-code"><?= esc($certificate['code']) ?></p>
            <div class="row" style="justify-content:center">
                <a class="btn btn-primary" href="<?= site_url('sertifikat/unduh') ?>"><?= icon('download') ?> Unduh PDF</a>
                <a class="btn" href="<?= site_url('verifikasi/' . $certificate['code']) ?>" target="_blank" rel="noopener"><?= icon('shield-check') ?> Halaman verifikasi</a>
            </div>
        </div>
    <?php elseif ($isGuest): ?>
        <div class="card empty-state">
            <img src="<?= base_url('assets/img/kodi.svg') ?>" alt="">
            <h3>Akun tamu tidak bisa mendapat sertifikat</h3>
            <p>Daftarkan akunmu, progres tamu ikut pindah.</p>
            <a class="btn btn-primary" href="<?= url_to('register') ?>">Daftar sekarang</a>
        </div>
    <?php else: ?>
        <div class="card">
            <h3>Belum memenuhi syarat</h3>
            <p>Sertifikat diberikan setelah kamu menamatkan 50 level dengan <strong>3 bintang di semua level</strong> (<?= (int) $target ?> bintang).</p>
            <div class="row"><strong><?= icon('star') ?> <?= (int) $stars ?> / <?= (int) $target ?></strong></div>
            <div class="progress" style="margin-top:10px"><span style="width: <?= round(min(100, $stars / max(1, $target) * 100), 1) ?>%"></span></div>
            <p class="small muted" style="margin-top:10px">Tips: ulangi level yang masih 2 bintang. Mengulang level yang sudah lulus tidak memotong hearts.</p>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
