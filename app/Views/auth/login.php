<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Masuk<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container">
    <div class="auth-wrap">
        <div class="auth-head">
            <img src="<?= base_url('assets/img/kodi.svg') ?>" alt="">
            <h1>Selamat datang lagi!</h1>
            <p class="muted">Masuk untuk melanjutkan misimu.</p>
        </div>

        <div class="card">
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
            <p class="small muted center" style="margin-top:10px">Login Google segera hadir.</p>
        </div>

        <?php if (setting('Auth.allowRegistration')): ?>
            <p class="center" style="margin-top:16px">Belum punya akun? <a href="<?= url_to('register') ?>">Daftar gratis</a></p>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
