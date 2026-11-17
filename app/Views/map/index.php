<?php
$bodyClass = 'page-map';
$currentLevel = null;
foreach ($map as $mapChapter) {
    foreach ($mapChapter['levels'] as $mapLevel) {
        if ($mapLevel['is_current']) {
            $currentLevel = $mapLevel;
            break 2;
        }
    }
}
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Peta Belajar<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container map-page">
    <?php if ($isAdmin): ?>
        <div class="admin-mode-banner">
            <?= icon('shield-check') ?>
            <span><strong>Mode admin aktif.</strong> Semua level terbuka untuk ditinjau.</span>
            <a href="<?= site_url('admin') ?>">Buka panel admin <?= icon('arrow-right') ?></a>
        </div>
    <?php endif ?>
    <div class="dash map-dashboard">
        <div>
            <?php foreach ($map as $chapter): ?>
                <?php $meta = chapter_meta($chapter['slug']) ?>
                <section class="chapter ch-<?= $meta['color'] ?> <?= $chapter['unlocked'] ? '' : 'is-locked' ?>" id="bab-<?= esc($chapter['slug'], 'attr') ?>">
                    <div class="chapter-head">
                        <div class="chapter-icon"><?= icon($chapter['unlocked'] ? $meta['icon'] : 'lock') ?></div>
                        <div>
                            <span class="module-label">MODUL <?= (int) $chapter['order_no'] ?></span>
                            <h2>Bab <?= (int) $chapter['order_no'] ?>: <?= esc($chapter['name']) ?></h2>
                            <p><?= esc($meta['tagline']) ?></p>
                        </div>
                        <span class="chapter-stars"><?= icon('star') ?> <?= (int) $chapter['stars'] ?>/<?= (int) $chapter['max_stars'] ?></span>
                    </div>

                    <?php if ($chapter['unlocked']): ?>
                    <div class="path">
                        <?php foreach ($chapter['levels'] as $level): ?>
                            <?php
                            $isBoss  = $level['type'] !== 'quiz';
                            $classes = ['node', 'is-' . $level['state']];
                            if ($isBoss) {
                                $classes[] = 'is-boss';
                            }
                            if ($level['is_current']) {
                                $classes[] = 'is-current';
                            }
                            $bubble = match (true) {
                                $level['state'] === 'locked'  => icon('lock'),
                                $level['type'] === 'mini_boss' => icon('sword'),
                                $level['type'] === 'final_boss' => icon('crown'),
                                default => (string) $level['global_no'],
                            };
                            ?>
                            <?php if ($isAdmin): ?><div class="node-admin-wrap"><?php endif ?>
                            <a class="<?= implode(' ', $classes) ?>" href="<?= site_url('level/' . $level['id']) ?>" <?= $level['unlocked'] ? '' : 'aria-disabled="true" tabindex="-1"' ?>>
                                <span class="node-bubble"><?= $bubble ?></span>
                                <?php if ($level['best_stars'] > 0): ?>
                                    <?= stars_html($level['best_stars']) ?>
                                <?php endif ?>
                                <span class="node-title">
                                    <?= $isBoss ? '<strong>' . esc(level_type_label($level['type'])) . '</strong><br>' : '' ?>
                                    <?= esc($level['title']) ?>
                                </span>
                                <?php if ($level['unlocked'] && ! $level['has_content']): ?>
                                    <span class="node-soon">Konten segera hadir</span>
                                <?php endif ?>
                            </a>
                            <?php if ($isAdmin): ?>
                                <a class="node-edit" href="<?= site_url('admin/level/' . $level['id']) ?>">
                                    <?= icon($isBoss ? 'terminal' : 'code') ?> <?= $isBoss ? 'Rincian' : 'Edit soal' ?>
                                </a>
                            </div>
                            <?php endif ?>
                        <?php endforeach ?>
                    </div>
                    <?php endif ?>
                </section>
            <?php endforeach ?>
        </div>

        <aside class="dash-side">
            <div class="card account-summary">
                <div class="account-summary-head"><div><span class="aside-eyebrow">STATUS AKUN</span><h3>Halo, <?= esc(display_name($currentUser)) ?>!</h3></div><span class="account-badge <?= (int) $currentUser->is_guest === 1 ? 'is-guest' : '' ?>"><i></i><?= (int) $currentUser->is_guest === 1 ? 'Sesi sementara' : 'Akun aktif' ?></span></div>
                <div class="stat-grid">
                    <div class="stat"><strong><?= (int) $summary['stars'] ?></strong><span>dari <?= (int) $summary['max_stars'] ?> bintang</span></div>
                    <div class="stat"><strong><?= (int) $summary['passed_levels'] ?></strong><span>level lulus</span></div>
                    <div class="stat"><strong><?= $rank === null ? '-' : '#' . (int) $rank ?></strong><span>peringkat</span></div>
                    <div class="stat"><strong><?= (int) round($summary['stars'] / max(1, $summary['max_stars']) * 100) ?>%</strong><span>menuju sertifikat</span></div>
                </div>
                <div class="account-progress-label"><span>Kelulusan kurikulum</span><span><?= (int) $summary['passed_levels'] ?> / 50 level</span></div>
                <div class="progress" style="margin-top:7px" aria-hidden="true"><span style="width: <?= round($summary['passed_levels'] / 50 * 100, 1) ?>%"></span></div>
            </div>

            <div class="card card-flat certificate-card">
                <h3><?= icon('award') ?> Sertifikat</h3>
                <?php if ($summary['stars'] >= $config->certificateStars): ?>
                    <p>Selamat! Semua level sudah 3 bintang.</p>
                    <a class="btn btn-primary btn-block" href="<?= site_url('sertifikat') ?>">Lihat sertifikat</a>
                <?php else: ?>
                    <p class="muted small">Tamatkan 50 level dengan 3 bintang semua (<?= (int) $config->certificateStars ?> bintang) untuk mendapatkan sertifikat ber-kode unik.</p>
                <?php endif ?>
            </div>

            <div class="kodi-card">
                <div class="kodi-card-copy"><span class="kodi-card-label">KODI SI ROBOT</span><p>"Siap mulai petualangan kodingmu hari ini?"</p></div>
                <img src="<?= base_url('assets/img/kodi.png') ?>" alt="Kodi siap menemani belajar">
                <?php if ($currentLevel !== null): ?>
                    <a href="<?= site_url('level/' . $currentLevel['id']) ?>"><?= icon('play') ?> Mulai Level <?= (int) $currentLevel['global_no'] ?> Sekarang</a>
                <?php else: ?>
                    <a href="<?= site_url('sertifikat') ?>"><?= icon('award') ?> Lihat pencapaianmu</a>
                <?php endif ?>
            </div>

            <div class="card card-flat how-to-card">
                <h3><?= icon('help') ?> Cara main</h3>
                <p class="muted small">Kuis: 9-10 benar = 3 bintang, 7-8 = 2, di bawah 7 = gagal. Boss: lulus di submit pertama = 3 bintang.</p>
                <button class="link-btn small" type="button" data-open-tutorial>Lihat tutorial lagi</button>
            </div>
        </aside>
    </div>
</div>

<div class="modal tutorial-modal <?= $showTutorial ? '' : 'hidden' ?>" id="tutorial" role="dialog" aria-modal="true" aria-labelledby="tut-title" data-done-url="<?= site_url('tutorial/selesai') ?>" data-seen="<?= $showTutorial ? '0' : '1' ?>">
    <div class="modal-card tutorial-card">
        <div class="tut-step is-active">
            <img src="<?= base_url('assets/img/kodi.png') ?>" alt="">
            <h2 id="tut-title">Hai, aku Kodi!</h2>
            <p>Aku akan menemanimu belajar coding dari nol. Ada <strong>50 level</strong> di 5 bab: HTML, CSS, JavaScript, SQL, dan PHP.</p>
        </div>
        <div class="tut-step">
            <h2><?= icon('bulb') ?> Kuis</h2>
            <p>Tiap level dibuka dengan kartu materi singkat, lalu 10 soal acak. Salah di jawaban pertama? Kamu kehilangan 1 heart, tapi muncul <strong>clue</strong> dan boleh coba lagi.</p>
        </div>
        <div class="tut-step">
            <h2><?= icon('sword') ?> Boss</h2>
            <p>Level 5 dan 10 di setiap bab adalah boss: tulis kode sungguhan di editor. Tombol <strong>Run</strong> bebas dipakai. Tombol <strong>Submit</strong> menjalankan test case. Gagal? AI tutor memberi petunjuk.</p>
        </div>
        <div class="tut-step">
            <h2><?= icon('heart') ?> Hearts &amp; bintang</h2>
            <p>Setiap level punya <?= (int) $config->maxHearts ?> hearts yang terisi lagi 1 setiap <?= (int) ceil($config->heartRefillSeconds / 60) ?> menit. Kumpulkan bintang untuk membuka bab berikutnya dan naik di papan peringkat.</p>
        </div>
        <div class="mcard-dots" data-tut-dots><span class="is-active"></span><span></span><span></span><span></span></div>
        <div class="row" style="justify-content:center">
            <button class="btn btn-ghost" type="button" data-tut-skip>Lewati</button>
            <button class="btn btn-primary" type="button" data-tut-next>Lanjut <?= icon('arrow-right') ?></button>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
