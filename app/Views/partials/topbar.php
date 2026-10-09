<?php
$currentUser ??= auth()->user();
$appName = config('Game')->appName;
$path    = trim(uri_string(), '/');
$active  = static fn (string $prefix) => str_starts_with($path, $prefix) ? 'is-active' : '';
$links   = [
    ['peta', 'map', 'Peta Belajar'],
    ['perpustakaan', 'book', 'Perpustakaan'],
    ['peringkat', 'trophy', 'Peringkat'],
    ['portofolio', 'folder', 'Portofolio'],
];
$isGuest = $currentUser !== null && (int) $currentUser->is_guest === 1;
?>
<header class="topbar">
    <div class="container topbar-inner">
        <a class="brand" href="<?= site_url($currentUser ? 'peta' : '/') ?>">
            <img src="<?= base_url('assets/img/logo.svg') ?>" alt="">
            <span><?= esc($appName) ?></span>
        </a>

        <?php if ($currentUser !== null): ?>
            <nav class="nav" id="main-nav">
                <?php foreach ($links as [$url, $ico, $label]): ?>
                    <a class="<?= $active($url) ?>" href="<?= site_url($url) ?>"><?= icon($ico) ?> <?= esc($label) ?></a>
                <?php endforeach ?>
            </nav>
            <span class="spacer"></span>
            <span class="stat-chip" title="Total bintang (level lulus)"><?= icon('star') ?> <?= (int) $currentUser->total_stars ?></span>
            <details class="user-menu">
                <summary class="avatar" aria-label="Menu akun"><?= esc(mb_strtoupper(mb_substr(display_name($currentUser), 0, 1))) ?></summary>
                <div class="dropdown">
                    <div class="dropdown-head">
                        <strong><?= esc(display_name($currentUser)) ?></strong><br>
                        <span class="muted small"><?= $isGuest ? 'Akun tamu' : esc($currentUser->email ?? '') ?></span>
                    </div>
                    <a href="<?= site_url('sertifikat') ?>"><?= icon('award') ?> Sertifikat</a>
                    <a href="<?= site_url('bantuan') ?>"><?= icon('help') ?> Bantuan</a>
                    <?php if ($isGuest): ?>
                        <a href="<?= url_to('register') ?>"><?= icon('user') ?> Daftar akun</a>
                        <form method="post" action="<?= site_url('tamu/keluar') ?>" data-confirm="Keluar dari mode tamu? Progres tamu akan ditinggalkan dan tidak bisa digabung.">
                            <?= csrf_field() ?>
                            <button type="submit"><?= icon('login') ?> Masuk ke akun lain</button>
                        </form>
                    <?php else: ?>
                        <a href="<?= url_to('logout') ?>"><?= icon('logout') ?> Keluar</a>
                    <?php endif ?>
                </div>
            </details>
            <button class="btn btn-ghost btn-sm nav-toggle" type="button" data-toggle-nav aria-label="Menu"><?= icon('menu') ?></button>
        <?php else: ?>
            <span class="spacer"></span>
            <a class="btn btn-ghost btn-sm" href="<?= url_to('login') ?>">Masuk</a>
            <a class="btn btn-primary btn-sm" href="<?= url_to('register') ?>">Daftar</a>
        <?php endif ?>
    </div>
</header>
