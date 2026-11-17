<?php $bodyClass = 'page-login' ?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Masuk<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container auth-screen">
    <div class="auth-wrap auth-login">
        <div class="card">
            <div class="auth-head">
                <img src="<?= base_url('assets/img/kodi.png') ?>" alt="">
                <h1>Selamat datang lagi!</h1>
                <p class="muted">Masuk untuk melanjutkan misimu.</p>
            </div>
            <form action="<?= url_to('login') ?>" method="post">
                <?= csrf_field() ?>

                <div class="field">
                    <label for="email">Email</label>
                    <input class="input" type="email" id="email" name="email" inputmode="email" autocomplete="email" value="<?= esc(old('email'), 'attr') ?>" required>
                </div>

                <div class="field">
                    <label for="password">Kata sandi</label>
                    <input class="input" type="password" id="password" name="password" autocomplete="current-password" required>
                </div>

                <?php if (setting('Auth.sessionConfig')['allowRemembering']): ?>
                    <div class="field">
                        <label class="check"><input type="checkbox" name="remember" <?= old('remember') ? 'checked' : '' ?>> Ingat saya</label>
                    </div>
                <?php endif ?>

                <button type="submit" class="btn btn-primary btn-block btn-lg">Masuk</button>
            </form>

            <div class="divider">atau</div>

            <form method="post" action="<?= site_url('tamu') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-block" type="submit"><?= icon('user') ?> Lanjut sebagai Tamu</button>
            </form>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
