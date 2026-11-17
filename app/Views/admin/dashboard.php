<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Admin<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="container admin-page">
    <div class="admin-page-head">
        <div><span class="section-kicker">PENGELOLAAN KONTEN</span><h1>Dashboard Admin</h1><p class="muted">Tinjau level dan kelola soal kuis dari satu tempat.</p></div>
        <a class="btn btn-primary" href="<?= site_url('admin/bank') ?>"><?= icon('search') ?> Buka bank soal</a>
    </div>
    <div class="admin-summary card"><span class="admin-summary-icon"><?= icon('book') ?></span><span><strong><?= (int) $totalQuestions ?></strong><small>Total soal di bank</small></span><a href="<?= site_url('admin/bank') ?>">Cari atau edit soal <?= icon('arrow-right') ?></a></div>

<div class="admin-table-wrap card">
<table class="table admin-table">
    <thead>
        <tr><th>Bab</th><th>Level</th><th>Judul</th><th>Tipe</th><th>Soal</th><th>Variasi boss</th><th></th></tr>
    </thead>
    <tbody>
        <?php foreach ($levels as $l): ?>
            <tr>
                <td><?= esc($l['chapter']) ?></td>
                <td><?= (int) $l['level_no'] ?></td>
                <td><?= esc($l['title']) ?></td>
                <td><?= esc($l['type']) ?></td>
                <td><?= (int) $l['questions'] ?></td>
                <td><?= (int) $l['challenges'] ?></td>
                <td>
                    <a class="btn btn-sm <?= $l['type'] === 'quiz' ? 'btn-primary' : '' ?>" href="<?= site_url('admin/level/' . $l['id']) ?>"><?= $l['type'] === 'quiz' ? 'Kelola soal' : 'Detail boss' ?></a>
                    <a class="btn btn-sm btn-ghost" href="<?= site_url('level/' . $l['id'] . '/main') ?>">Main</a>
                </td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>
</div>
</div>
<?= $this->endSection() ?>
