<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Portofolio<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container">
    <h1><?= icon('folder') ?> Portofolio</h1>
    <p class="muted">Kode terakhir yang lulus di setiap level boss. Bukti bahwa kamu sudah bisa ngoding!</p>

    <?php if ($items === []): ?>
        <div class="empty-state card">
            <img src="<?= base_url('assets/img/kodi.svg') ?>" alt="">
            <h3>Portofolio masih kosong</h3>
            <p>Kalahkan mini boss pertama (level 5) untuk menambahkan karya pertamamu.</p>
            <a class="btn btn-primary" href="<?= site_url('peta') ?>">Ke peta belajar</a>
        </div>
    <?php else: ?>
        <div class="port-grid">
            <?php foreach ($items as $item): ?>
                <?php $meta = chapter_meta($item['slug']) ?>
                <article class="card port-item ch-<?= $meta['color'] ?>">
                    <div class="row" style="margin-bottom:8px">
                        <span class="badge badge-boss"><?= icon($item['type'] === 'final_boss' ? 'crown' : 'sword') ?> <?= esc(level_type_label($item['type'])) ?></span>
                        <span class="badge"><?= esc($item['chapter_name']) ?></span>
                    </div>
                    <h3>Level <?= (int) (($item['order_no'] - 1) * 10 + $item['level_no']) ?>: <?= esc($item['title']) ?></h3>
                    <p class="small muted">Lulus <?= esc(tanggal_id($item['created_at'])) ?></p>
                    <pre><code><?= esc($item['code']) ?></code></pre>
                    <?php if ($item['runner'] === 'iframe'): ?>
                        <button class="btn btn-sm" type="button" data-preview><?= icon('eye') ?> Lihat hasil</button>
                        <!-- Kode user hanya dijalankan di iframe sandbox tanpa allow-same-origin -->
                        <template data-code data-lang="<?= esc($item['slug'], 'attr') ?>"><?= esc($item['code']) ?></template>
                    <?php endif ?>
                </article>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('[data-preview]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var tpl = btn.parentElement.querySelector('template[data-code]');
    var code = tpl.content.textContent;
    var doc = tpl.dataset.lang === 'javascript'
      ? '<!doctype html><meta charset="utf-8"><body><script>' + code.replace(/<\/script/gi, '<\\/script') + '<\/script>'
      : code;
    var frame = document.createElement('iframe');
    frame.setAttribute('sandbox', 'allow-scripts');
    frame.setAttribute('title', 'Hasil kode');
    frame.srcdoc = doc;
    btn.replaceWith(frame);
  });
});
</script>
<?= $this->endSection() ?>
