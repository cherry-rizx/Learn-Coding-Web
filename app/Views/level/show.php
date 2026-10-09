<?php
$meta     = chapter_meta($chapter['slug']);
$isBoss   = $level['type'] !== 'quiz';
$blocked  = ! $isReplay && $hearts['hearts'] === 0;
$canStart = $level['has_content'] && ! $blocked;
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Level <?= (int) $level['global_no'] ?>: <?= esc($level['title']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container container-narrow ch-<?= $meta['color'] ?>">
    <a class="small" href="<?= site_url('peta#bab-' . $chapter['slug']) ?>"><?= icon('arrow-left') ?> Peta belajar</a>

    <div class="level-hero <?= $isBoss ? 'is-boss' : '' ?>" style="margin-top:12px">
        <div class="level-num"><?= $isBoss ? icon($level['type'] === 'final_boss' ? 'crown' : 'sword') : (int) $level['global_no'] ?></div>
        <div>
            <div class="row" style="gap:8px">
                <span class="badge <?= $isBoss ? 'badge-boss' : '' ?>"><?= esc(level_type_label($level['type'])) ?></span>
                <span class="badge">Bab <?= (int) $chapter['order_no'] ?> &middot; <?= esc($chapter['name']) ?></span>
                <?php if ($level['best_stars'] > 0): ?><?= stars_html($level['best_stars']) ?><?php endif ?>
            </div>
            <h1>Level <?= (int) $level['global_no'] ?>: <?= esc($level['title']) ?></h1>
        </div>
    </div>

    <?php if ($cards !== []): ?>
        <div class="card mcards" data-cards>
            <?php foreach ($cards as $i => $card): ?>
                <article class="mcard <?= $i === 0 ? 'is-active' : '' ?>">
                    <span class="badge"><?= $isBoss ? 'Ringkasan' : 'Materi' ?> <?= $i + 1 ?>/<?= count($cards) ?></span>
                    <h2 style="margin-top:10px"><?= esc($card['title']) ?></h2>
                    <div class="mcard-body"><?= format_text($card['body']) ?></div>
                    <?php if (! empty($card['code_example'])): ?>
                        <pre><code><?= esc($card['code_example']) ?></code></pre>
                    <?php endif ?>
                </article>
            <?php endforeach ?>

            <?php if (count($cards) > 1): ?>
                <div class="mcard-dots" data-dots>
                    <?php foreach ($cards as $i => $card): ?><span class="<?= $i === 0 ? 'is-active' : '' ?>"></span><?php endforeach ?>
                </div>
                <div class="row" style="justify-content:space-between">
                    <button class="btn" type="button" data-prev disabled><?= icon('arrow-left') ?> Sebelumnya</button>
                    <button class="btn" type="button" data-next>Berikutnya <?= icon('arrow-right') ?></button>
                </div>
            <?php endif ?>
        </div>
    <?php endif ?>

    <div class="card" style="margin-top:20px">
        <?php if (! $level['has_content']): ?>
            <div class="empty-state">
                <img src="<?= base_url('assets/img/kodi.svg') ?>" alt="">
                <h3>Konten level ini segera hadir</h3>
                <p>Soal dan tantangan untuk level ini sedang disiapkan.</p>
            </div>
        <?php elseif ($blocked): ?>
            <div class="wait-box">
                <?= $this->include('partials/hearts') ?>
                <h3 style="margin-top:12px">Hearts kamu habis</h3>
                <p class="muted">Heart berikutnya terisi dalam</p>
                <div class="big-timer" data-countdown="<?= (int) $hearts['next_in'] ?>" data-reload-on-zero>--:--</div>
                <p class="muted">Sambil menunggu, baca lagi kartu materi di atas atau buka perpustakaan.</p>
                <a class="btn" href="<?= site_url('perpustakaan?level=' . $level['id']) ?>"><?= icon('book') ?> Buka materi level ini</a>
            </div>
        <?php else: ?>
            <div class="start-box">
                <div>
                    <?= $this->include('partials/hearts') ?>
                    <p class="small muted" style="margin:6px 0 0">
                        <?php if ($isReplay): ?>
                            Level ini sudah lulus. Mengulang tidak memotong hearts, dan hanya bintang terbaik yang dihitung.
                        <?php elseif ($isBoss): ?>
                            Submit yang gagal memotong <?= (int) $cost ?> hearts. Run bebas tanpa penalti.
                        <?php else: ?>
                            Jawaban pertama yang salah memotong <?= (int) $cost ?> heart.
                        <?php endif ?>
                    </p>
                </div>
                <a class="btn <?= $isBoss ? 'btn-boss' : 'btn-primary' ?> btn-lg" href="<?= site_url('level/' . $level['id'] . '/main') ?>">
                    <?= icon('play') ?> <?= $active !== null ? 'Lanjutkan' : ($isBoss ? 'Lawan Boss' : 'Mulai Kuis') ?>
                </a>
            </div>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
