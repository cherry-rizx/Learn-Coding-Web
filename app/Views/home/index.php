<?php $bodyClass = 'page-home' ?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Belajar coding dari nol<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container">
    <section class="home-hero">
        <div class="home-copy">
            <span class="home-kicker"><span></span> 50 MISI, 5 BAB <b>•</b> BELAJAR DARI NOL</span>
            <h1>Belajar coding dari nol, satu misi kecil <em>setiap hari.</em></h1>
            <p class="lead">Baca kartu materi ringkas, selesaikan tantangan kode harian, lalu taklukkan boss langsung di browser. Kodi dan AI tutor siap membantumu memahami konsepnya.</p>
            <div class="home-actions">
                <a class="btn btn-primary btn-lg" href="<?= url_to('register') ?>">Mulai Belajar Sekarang <?= icon('arrow-right') ?></a>
                <form method="post" action="<?= site_url('tamu') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-lg" type="submit"><?= icon('user') ?> Coba sebagai Tamu</button>
                </form>
            </div>
            <p class="home-signin">Sudah punya akun? <a href="<?= url_to('login') ?>">Masuk di sini</a><span>•</span><strong>★</strong> Belajar dengan ritmemu sendiri</p>
            <div class="home-highlights" id="fitur">
                <div><span class="highlight-icon is-blue"><?= icon('bolt') ?></span><span><strong>5 menit / hari</strong><small>Mikro-learning</small></span></div>
                <div><span class="highlight-icon is-cyan"><?= icon('bulb') ?></span><span><strong>AI Mentor</strong><small>Feedback instan</small></span></div>
                <div><span class="highlight-icon is-gold"><?= icon('trophy') ?></span><span><strong>Boss Battle</strong><small>Uji portofolio</small></span></div>
            </div>
        </div>
        <div class="home-art" aria-label="Kodi, maskot Misi Koding">
            <div class="art-halo"></div>
            <img src="<?= base_url('assets/img/kodi.png') ?>" alt="Kodi, robot tutor Misi Koding">
            <div class="art-note art-note-ai"><span></span><div><small>AI TUTOR</small><strong>Siap membantu 24/7</strong></div></div>
            <div class="art-note art-note-code"><span><?= icon('code') ?></span><div><small>TANTANGAN HARI INI</small><strong>const solve = () =&gt; boss();</strong></div></div>
        </div>
    </section>

    <section class="curriculum" id="kurikulum">
        <div class="curriculum-heading">
            <div><span class="section-kicker">KURIKULUM MISI TERSTRUKTUR</span><h2>Jalur Belajar Bertahap</h2><p>Mulai dari fondasi HTML sampai logika backend. Buka modul berikutnya dengan menaklukkan boss.</p></div>
            <span class="curriculum-status"><i></i> 5 jalur belajar</span>
        </div>
        <div class="curriculum-grid">
            <?php foreach ([
                ['html', 'HTML', 'Level 1–10', 'DASAR WEB', '<>', 'Tag, struktur DOM, form interaktif, semantik modern, dan SEO web.'],
                ['css', 'CSS', 'Level 11–20', 'STYLING UI', '✦', 'Box model, Flexbox, CSS Grid, animasi, hingga responsive web design.'],
                ['javascript', 'JavaScript', 'Level 21–30', 'LOGIKA KODE', 'ϟ', 'Variabel, fungsi, DOM manipulation, event listener, dan async.'],
                ['sql', 'SQL', 'Level 31–40', 'BASIS DATA', '▤', 'Query SELECT, filtering, relasi tabel JOIN, indexing, dan CRUD.'],
                ['php', 'PHP', 'Level 41–50', 'BACKEND API', '⌘', 'Server-side scripting, RESTful API endpoint, autentikasi, dan boss akhir.'],
            ] as [$slug, $name, $range, $label, $mark, $description]): ?>
                <?php $meta = chapter_meta($slug) ?>
                <article class="curriculum-card ch-<?= $meta['color'] ?>">
                    <div class="curriculum-card-top"><span class="curriculum-mark"><?= esc($mark) ?></span><span class="curriculum-tag"><?= esc($label) ?></span></div>
                    <h3><?= esc($name) ?></h3><span class="curriculum-range"><?= esc($range) ?></span>
                    <p><?= esc($description) ?></p>
                    <div class="curriculum-card-bottom"><span><i></i> 10 misi</span><a href="<?= url_to('register') ?>" aria-label="Mulai jalur <?= esc($name, 'attr') ?>">→</a></div>
                </article>
            <?php endforeach ?>
        </div>
    </section>

</div>
<?= $this->endSection() ?>
