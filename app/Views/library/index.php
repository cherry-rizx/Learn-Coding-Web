<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Perpustakaan Materi<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container container-narrow">
    <h1><?= icon('book') ?> Perpustakaan Materi</h1>
    <p class="muted">Semua kartu materi dari level yang sudah terbuka. Level yang masih terkunci belum ditampilkan.</p>

    <div class="lib-search">
        <?= icon('search') ?>
        <input class="input" type="search" placeholder="Cari materi, misal: tabel, flexbox, SELECT..." data-lib-search aria-label="Cari materi">
    </div>

    <?php if ($groups === []): ?>
        <div class="empty-state card">
            <img src="<?= base_url('assets/img/kodi.svg') ?>" alt="">
            <h3>Belum ada materi</h3>
            <p>Buka level pertama di peta belajar untuk mulai mengumpulkan materi.</p>
        </div>
    <?php endif ?>

    <?php foreach ($groups as $group): ?>
        <?php $meta = chapter_meta($group['chapter']['slug']) ?>
        <section class="lib-chapter ch-<?= $meta['color'] ?>">
            <h2><?= icon($meta['icon']) ?> Bab <?= (int) $group['chapter']['order_no'] ?>: <?= esc($group['chapter']['name']) ?></h2>

            <?php foreach ($group['levels'] as $levelId => $item): ?>
                <details class="card card-flat lib-level" style="padding:0" id="level-<?= (int) $levelId ?>" <?= $focus === (int) $levelId ? 'open' : '' ?> data-lib-item>
                    <summary>
                        <span class="badge <?= $item['level']['type'] === 'quiz' ? '' : 'badge-boss' ?>">Level <?= (int) $item['level']['global_no'] ?></span>
                        <?= esc($item['level']['title']) ?>
                    </summary>
                    <div class="lib-cards">
                        <?php foreach ($item['cards'] as $card): ?>
                            <article class="lib-card">
                                <h3><?= esc($card['title']) ?></h3>
                                <?= format_text($card['body']) ?>
                                <?php if (! empty($card['code_example'])): ?>
                                    <pre><code><?= esc($card['code_example']) ?></code></pre>
                                <?php endif ?>
                            </article>
                        <?php endforeach ?>
                        <a class="small" href="<?= site_url('level/' . $levelId) ?>">Buka level ini <?= icon('arrow-right') ?></a>
                    </div>
                </details>
            <?php endforeach ?>
        </section>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Pencarian sederhana di sisi browser
(function () {
  var input = document.querySelector('[data-lib-search]');
  var items = document.querySelectorAll('[data-lib-item]');
  var focused = document.querySelector('[data-lib-item][open]');
  if (focused) focused.scrollIntoView({ block: 'start' });
  input.addEventListener('input', function () {
    var q = input.value.trim().toLowerCase();
    items.forEach(function (item) {
      var hit = q === '' || item.textContent.toLowerCase().indexOf(q) !== -1;
      item.classList.toggle('hidden', !hit);
      if (q !== '' && hit) item.open = true;
    });
  });
})();
</script>
<?= $this->endSection() ?>
