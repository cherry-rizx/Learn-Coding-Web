<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Papan Peringkat<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container container-narrow">
    <h1><?= icon('trophy') ?> Papan Peringkat</h1>
    <p class="muted">Sepanjang masa, berdasarkan total bintang dari level yang lulus (maksimal 150). Kalau seri, yang lebih dulu mencapai angka itu berada di atas.</p>

    <?php if ($isGuest): ?>
        <div class="alert alert-warn"><?= icon('alert') ?> <div>Akun tamu tidak masuk leaderboard. <a href="<?= url_to('register') ?>">Daftar</a> supaya namamu muncul di sini.</div></div>
    <?php elseif ($myRank !== null): ?>
        <div class="alert alert-success"><?= icon('star') ?> <div>Peringkatmu saat ini: <strong>#<?= (int) $myRank ?></strong> dengan <strong><?= (int) $myStars ?></strong> bintang.</div></div>
    <?php else: ?>
        <div class="alert"><?= icon('target') ?> <div>Luluskan level pertamamu untuk masuk papan peringkat.</div></div>
    <?php endif ?>

    <?php if ($rows === []): ?>
        <div class="empty-state card"><img src="<?= base_url('assets/img/kodi.svg') ?>" alt=""><h3>Belum ada yang masuk peringkat</h3><p>Jadilah yang pertama!</p></div>
    <?php else: ?>
        <?php $top = array_slice($rows, 0, 3) ?>
        <div class="podium">
            <?php foreach ([1, 0, 2] as $pos): ?>
                <?php if (isset($top[$pos])): ?>
                    <div class="card <?= $pos === 0 ? 'p1' : '' ?>">
                        <div class="rank rank-<?= $pos + 1 ?>" style="margin:0 auto 8px"><?= $pos + 1 ?></div>
                        <strong><?= esc($top[$pos]['username']) ?></strong>
                        <div class="muted"><?= icon('star') ?> <?= (int) $top[$pos]['total_stars'] ?> bintang</div>
                    </div>
                <?php endif ?>
            <?php endforeach ?>
        </div>

        <div class="card" style="padding:0;overflow-x:auto">
            <table class="table">
                <thead><tr><th>#</th><th>Nama</th><th>Bintang</th><th>Dicapai</th></tr></thead>
                <tbody>
                    <?php foreach ($rows as $i => $row): ?>
                        <tr class="<?= (int) $row['id'] === (int) auth()->id() ? 'is-me' : '' ?>">
                            <td><span class="rank <?= $i < 3 ? 'rank-' . ($i + 1) : '' ?>"><?= $i + 1 ?></span></td>
                            <td><?= esc($row['username']) ?></td>
                            <td><?= icon('star') ?> <?= (int) $row['total_stars'] ?></td>
                            <td class="muted small"><?= esc(tanggal_id($row['stars_updated_at'])) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
