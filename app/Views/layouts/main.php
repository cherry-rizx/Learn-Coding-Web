<?php
/**
 * Layout utama. Section: title, content, scripts, head (opsional).
 * $currentUser dikirim BaseController::render().
 */
$currentUser ??= auth()->user();
$appName     = config('Game')->appName;
$isGuest     = $currentUser !== null && (int) $currentUser->is_guest === 1;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-header" content="<?= esc(csrf_header(), 'attr') ?>">
    <meta name="csrf-token" content="<?= esc(csrf_hash(), 'attr') ?>">
    <title><?= $this->renderSection('title') ?> · <?= esc($appName) ?></title>
    <link rel="icon" href="<?= base_url('favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <?= $this->renderSection('head') ?>
</head>
<body class="<?= esc($bodyClass ?? '', 'attr') ?>">
<?= icon_sprite() ?>
<?= $this->include('partials/topbar') ?>

<?php if ($isGuest): ?>
    <div class="guest-banner">
        <div class="container">
            <?= icon('alert') ?>
            <span><strong>Mode tamu:</strong> progres tidak dijamin tersimpan, tidak masuk leaderboard, dan tidak bisa mendapat sertifikat.</span>
            <a class="btn btn-sm btn-primary" href="<?= url_to('register') ?>">Daftar &amp; simpan progres</a>
        </div>
    </div>
<?php endif ?>

<main>
    <div class="container">
        <?= $this->include('partials/flash') ?>
    </div>
    <?= $this->renderSection('content') ?>
</main>

<footer class="footer">
    <div class="container">
        <span><?= esc($appName) ?> &middot; Project akhir Framework PHP (CodeIgniter 4)</span>
        <span><a href="<?= site_url('bantuan') ?>">Bantuan</a></span>
    </div>
</footer>

<script src="<?= base_url('assets/js/app.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
