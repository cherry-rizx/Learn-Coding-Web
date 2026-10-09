<?php $upgrade ??= false ?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Daftar<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container">
    <div class="auth-wrap">
        <div class="auth-head">
            <img src="<?= base_url('assets/img/kodi.svg') ?>" alt="">
            <h1><?= $upgrade ? 'Simpan progres tamu' : 'Buat akun' ?></h1>
            <p class="muted">
                <?= $upgrade
                    ? 'Akun tamumu akan di-upgrade jadi akun biasa. Semua bintang dan progres ikut pindah.'
                    : 'Gratis. Progres tersimpan, masuk leaderboard, dan bisa mendapat sertifikat.' ?>
            </p>
        </div>

        <?php if (session('email_taken')): ?>
            <div class="alert alert-warn">
                <?= icon('alert') ?>
                <div>
                    <strong>Email sudah terdaftar.</strong> Progres tamu tidak bisa digabung ke akun yang sudah ada.
                    Kalau itu akunmu, keluar dari mode tamu lalu masuk.
                    <form method="post" action="<?= site_url('tamu/keluar') ?>" style="margin-top:8px">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm" type="submit"><?= icon('login') ?> Keluar dari mode tamu &amp; masuk</button>
                    </form>
                </div>
            </div>
        <?php endif ?>

        <div class="card">
            <form action="<?= url_to('register') ?>" method="post">
                <?= csrf_field() ?>

                <div class="field">
                    <label for="username">Nama pengguna</label>
                    <input class="input" type="text" id="username" name="username" autocomplete="username" value="<?= esc(old('username'), 'attr') ?>" required minlength="3" maxlength="30">
                    <span class="small muted">Tampil di leaderboard dan sertifikat.</span>
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input class="input" type="email" id="email" name="email" inputmode="email" autocomplete="email" value="<?= esc(old('email'), 'attr') ?>" required>
                </div>

                <div class="field">
                    <label for="password">Kata sandi</label>
                    <input class="input" type="password" id="password" name="password" autocomplete="new-password" required minlength="8">
                    <span class="small muted">Minimal 8 karakter, jangan memakai nama atau email.</span>
                </div>

                <div class="field">
                    <label for="password_confirm">Ulangi kata sandi</label>
                    <input class="input" type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg"><?= $upgrade ? 'Upgrade akun' : 'Daftar' ?></button>
            </form>

            <?php if (! $upgrade): ?>
                <div class="divider">atau</div>
                <form method="post" action="<?= site_url('tamu') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-block" type="submit"><?= icon('user') ?> Coba dulu sebagai Tamu</button>
                </form>
            <?php endif ?>
        </div>

        <?php if (! $upgrade): ?>
            <p class="center" style="margin-top:16px">Sudah punya akun? <a href="<?= url_to('login') ?>">Masuk</a></p>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
