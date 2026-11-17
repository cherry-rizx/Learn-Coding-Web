<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Admin · Bank Soal<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="container admin-page">
    <div class="admin-page-head">
        <div>
            <a class="admin-back" href="<?= site_url('admin') ?>"><?= icon('arrow-left') ?> Dashboard admin</a>
            <h1>Bank soal</h1>
            <p class="muted">Cari, tinjau, dan edit pertanyaan kuis pada semua level.</p>
        </div>
        <span class="admin-total"><?= count($rows) ?> soal ditemukan</span>
    </div>

    <form class="admin-filters card" method="get" action="<?= site_url('admin/bank') ?>">
        <label>Level
            <select class="input" name="level">
                <option value="0">Semua level</option>
                <?php foreach ($levels as $level): ?>
                    <option value="<?= (int) $level['id'] ?>" <?= (int) $filters['level'] === (int) $level['id'] ? 'selected' : '' ?>>
                        Bab <?= (int) $level['order_no'] ?> · Level <?= (int) $level['level_no'] ?> — <?= esc($level['title']) ?>
                    </option>
                <?php endforeach ?>
            </select>
        </label>
        <label>Tipe soal
            <select class="input" name="type">
                <option value="">Semua tipe</option>
                <option value="mcq" <?= $filters['type'] === 'mcq' ? 'selected' : '' ?>>Pilihan ganda</option>
                <option value="arrange" <?= $filters['type'] === 'arrange' ? 'selected' : '' ?>>Susun kata</option>
            </select>
        </label>
        <label class="admin-search">Cari pertanyaan
            <input class="input" type="search" name="q" value="<?= esc($filters['q'], 'attr') ?>" placeholder="Ketik kata kunci…">
        </label>
        <div class="admin-filter-actions"><button class="btn btn-primary" type="submit"><?= icon('search') ?> Cari</button><a class="btn" href="<?= site_url('admin/bank') ?>">Reset</a></div>
    </form>

    <?php if ($rows === []): ?>
        <div class="card empty-state"><h2>Belum ada soal yang cocok</h2><p>Coba ubah filter atau buka halaman level untuk menambahkan soal.</p></div>
    <?php else: ?>
        <div class="admin-table-wrap card">
            <table class="table admin-table">
                <thead><tr><th>Bab / Level</th><th>Tipe</th><th>Pertanyaan</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><strong><?= esc($row['chapter']) ?></strong><small>Level <?= (int) $row['level_no'] ?></small></td>
                            <td><span class="admin-type"><?= $row['type'] === 'mcq' ? 'Pilihan ganda' : 'Susun kata' ?></span></td>
                            <td><?= esc($row['prompt']) ?></td>
                            <td class="admin-row-actions">
                                <a class="btn btn-sm btn-primary" href="<?= site_url('admin/soal/' . $row['id']) ?>">Edit</a>
                                <a class="btn btn-sm" href="<?= site_url('admin/level/' . $row['level_id']) ?>">Level</a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
