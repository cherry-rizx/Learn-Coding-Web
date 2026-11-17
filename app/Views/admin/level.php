<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Admin · Level <?= (int) $level['level_no'] ?><?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="container admin-page">
<a class="admin-back" href="<?= site_url('admin') ?>"><?= icon('arrow-left') ?> Dashboard admin</a>
<div class="admin-page-head"><div><span class="section-kicker"><?= esc($chapter['name']) ?> · <?= esc($level['type']) ?></span><h1>Level <?= (int) $level['level_no'] ?>: <?= esc($level['title']) ?></h1></div>
<?php if ($level['type'] === 'quiz'): ?><div class="admin-actions"><a class="btn btn-primary" href="<?= site_url('admin/level/' . $level['id'] . '/soal/baru?type=mcq') ?>">+ Pilihan ganda</a><a class="btn" href="<?= site_url('admin/level/' . $level['id'] . '/soal/baru?type=arrange') ?>">+ Susun kata</a></div><?php endif ?>
</div>

<section class="admin-section card"><h2>Kartu materi</h2>
<?php foreach ($cards as $card): ?>
    <p class="admin-material-title"><strong><?= esc($card['title']) ?></strong></p>
<?php endforeach ?>
</section>

<?php if ($level['type'] === 'quiz'): ?>
    <h2 class="admin-section-title">Soal <span><?= count($questions) ?></span></h2>

    <?php foreach ($questions as $q): ?>
        <div class="card admin-question-card">
            <div class="admin-question-heading"><div><span class="admin-type"><?= $q['type'] === 'mcq' ? 'Pilihan ganda' : 'Susun kata' ?></span><p><strong><?= esc($q['prompt']) ?></strong></p></div><a class="btn btn-sm btn-primary" href="<?= site_url('admin/soal/' . $q['id']) ?>">Edit soal</a></div>
            <ul class="admin-option-list">
                <?php foreach ($q['options'] as $o): ?>
                    <li>
                        <?= esc($o['content']) ?>
                        <small>
                            <?php if ($q['type'] === 'mcq'): ?>
                                <?= (int) $o['is_correct'] ? '(benar)' : '' ?>
                            <?php else: ?>
                                <?= $o['correct_position'] !== null ? '(urutan ' . $o['correct_position'] . ')' : '(pengecoh)' ?>
                            <?php endif ?>
                        </small>
                    </li>
                <?php endforeach ?>
            </ul>
            <div class="admin-row-actions">
            <form method="post" action="<?= site_url('admin/soal/' . $q['id'] . '/duplikat') ?>">
                <?= csrf_field() ?><button class="btn btn-sm" type="submit">Duplikat</button>
            </form>
            <form method="post" action="<?= site_url('admin/soal/' . $q['id'] . '/hapus') ?>" data-confirm="Hapus soal ini?">
                <?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">Hapus</button>
            </form>
            </div>
        </div>
    <?php endforeach ?>
<?php else: ?>
    <h2 class="admin-section-title">Variasi boss <span><?= count($challenges) ?></span></h2>
    <?php foreach ($challenges as $c): ?>
        <div class="card">
            <p><strong>Variasi <?= (int) $c['variant_no'] ?></strong></p>
            <p><?= esc($c['instruction']) ?></p>
            <pre><?= esc($c['solution_code']) ?></pre>
        </div>
    <?php endforeach ?>
<?php endif ?>
</div>
<?= $this->endSection() ?>
