<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Belajar coding dari nol<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container">
    <section class="hero">
        <div>
            <span class="badge badge-ok"><?= icon('target') ?> 50 misi, 5 bab</span>
            <h1>Belajar coding dari nol, satu misi kecil setiap hari.</h1>
            <p class="lead">Baca kartu materi singkat, jawab kuis, lalu kalahkan boss dengan menulis kode sungguhan langsung di browser. Kalau buntu, AI tutor siap memberi petunjuk tanpa membocorkan jawaban.</p>
            <div class="row">
                <a class="btn btn-primary btn-lg" href="<?= url_to('register') ?>">Mulai Belajar <?= icon('arrow-right') ?></a>
                <form method="post" action="<?= site_url('tamu') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-lg" type="submit"><?= icon('user') ?> Coba sebagai Tamu</button>
                </form>
            </div>
            <p class="small muted" style="margin-top:12px">Sudah punya akun? <a href="<?= url_to('login') ?>">Masuk di sini</a>.</p>
        </div>
        <div class="hero-art"><img src="<?= base_url('assets/img/kodi.svg') ?>" alt="Kodi, maskot robot"></div>
    </section>

    <div class="chapter-strip">
        <?php foreach ([['html', 'HTML', 'Level 1-10'], ['css', 'CSS', 'Level 11-20'], ['javascript', 'JavaScript', 'Level 21-30'], ['sql', 'SQL', 'Level 31-40'], ['php', 'PHP', 'Level 41-50']] as [$slug, $name, $range]): ?>
            <?php $meta = chapter_meta($slug) ?>
            <div class="chapter-pill ch-<?= $meta['color'] ?>">
                <?= icon($meta['icon']) ?>
                <span><?= esc($name) ?></span>
                <small><?= esc($range) ?></small>
            </div>
        <?php endforeach ?>
    </div>

    <section class="feature-grid">
        <div class="card feature">
            <div class="feature-icon"><?= icon('book') ?></div>
            <h3>Materi singkat</h3>
            <p class="muted">2-3 kartu materi per level, lengkap dengan contoh kode. Bisa dibuka lagi kapan saja di perpustakaan.</p>
        </div>
        <div class="card feature">
            <div class="feature-icon"><?= icon('bulb') ?></div>
            <h3>Kuis dengan clue</h3>
            <p class="muted">Salah? Muncul clue dan kamu boleh coba lagi. Yang dinilai hanya jawaban pertama.</p>
        </div>
        <div class="card feature">
            <div class="feature-icon"><?= icon('sword') ?></div>
            <h3>Boss = ngoding beneran</h3>
            <p class="muted">Level 5 dan 10 tiap bab adalah tantangan kode di sandbox. Hasilnya tampil langsung.</p>
        </div>
        <div class="card feature">
            <div class="feature-icon"><?= icon('award') ?></div>
            <h3>Bintang &amp; sertifikat</h3>
            <p class="muted">Kumpulkan bintang, naik di papan peringkat, dan raih sertifikat dengan 150 bintang penuh.</p>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
