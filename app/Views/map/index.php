<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Peta Belajar<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container">
    <div class="dash">
        <div>
            <?php foreach ($map as $chapter): ?>
                <?php $meta = chapter_meta($chapter['slug']) ?>
                <section class="chapter ch-<?= $meta['color'] ?> <?= $chapter['unlocked'] ? '' : 'is-locked' ?>" id="bab-<?= esc($chapter['slug'], 'attr') ?>">
                    <div class="chapter-head">
                        <div class="chapter-icon"><?= icon($chapter['unlocked'] ? $meta['icon'] : 'lock') ?></div>
                        <div>
                            <h2>Bab <?= (int) $chapter['order_no'] ?>: <?= esc($chapter['name']) ?></h2>
                            <p><?= esc($meta['tagline']) ?></p>
                        </div>
                        <span class="chapter-stars"><?= icon('star') ?> <?= (int) $chapter['stars'] ?>/<?= (int) $chapter['max_stars'] ?></span>
                    </div>

                    <?php if (! $chapter['unlocked']): ?>
                        <div class="chapter-lock-note">
                            <?= icon('lock') ?>
                            Terbuka setelah final boss bab sebelumnya lulus dan bab itu punya minimal <?= (int) $config->chapterUnlockStars ?> bintang.
                        </div>
                    <?php endif ?>

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
                        <?php endforeach ?>
                    </div>
                </section>
            <?php endforeach ?>
        </div>

        <aside class="dash-side">
            <div class="card">
                <h3>Halo, <?= esc(display_name($currentUser)) ?>!</h3>
                <div class="stat-grid">
                    <div class="stat"><strong><?= (int) $summary['stars'] ?></strong><span>dari <?= (int) $summary['max_stars'] ?> bintang</span></div>
                    <div class="stat"><strong><?= (int) $summary['passed_levels'] ?></strong><span>level lulus</span></div>
                    <div class="stat"><strong><?= $rank === null ? '-' : '#' . (int) $rank ?></strong><span>peringkat</span></div>
                    <div class="stat"><strong><?= (int) round($summary['stars'] / max(1, $summary['max_stars']) * 100) ?>%</strong><span>menuju sertifikat</span></div>
                </div>
                <div class="progress" style="margin-top:14px" aria-hidden="true"><span style="width: <?= round($summary['stars'] / max(1, $summary['max_stars']) * 100, 1) ?>%"></span></div>
            </div>

            <div class="card card-flat">
                <h3><?= icon('award') ?> Sertifikat</h3>
                <?php if ($summary['stars'] >= $config->certificateStars): ?>
                    <p>Selamat! Semua level sudah 3 bintang.</p>
                    <a class="btn btn-primary btn-block" href="<?= site_url('sertifikat') ?>">Lihat sertifikat</a>
                <?php else: ?>
                    <p class="muted small">Tamatkan 50 level dengan 3 bintang semua (<?= (int) $config->certificateStars ?> bintang) untuk mendapatkan sertifikat ber-kode unik.</p>
                <?php endif ?>
            </div>

            <div class="card card-flat">
                <h3><?= icon('help') ?> Cara main</h3>
                <p class="muted small">Kuis: 9-10 benar = 3 bintang, 7-8 = 2, di bawah 7 = gagal. Boss: lulus di submit pertama = 3 bintang.</p>
                <button class="link-btn small" type="button" data-open-tutorial>Lihat tutorial lagi</button>
            </div>
        </aside>
    </div>
</div>

<div class="modal <?= $showTutorial ? '' : 'hidden' ?>" id="tutorial" role="dialog" aria-modal="true" aria-labelledby="tut-title" data-done-url="<?= site_url('tutorial/selesai') ?>" data-seen="<?= $showTutorial ? '0' : '1' ?>">
    <div class="modal-card">
        <div class="tut-step is-active">
            <img src="<?= base_url('assets/img/kodi.svg') ?>" alt="">
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
