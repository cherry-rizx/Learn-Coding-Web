<?php
$meta      = chapter_meta($chapter['slug']);
$challenge = $page['challenge'];
$langLabel = ['html' => 'HTML', 'css' => 'HTML + CSS', 'javascript' => 'JavaScript', 'sql' => 'SQL (SQLite)', 'php' => 'PHP'][$chapter['slug']] ?? 'Kode';
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc(level_type_label($level['type'])) ?>: <?= esc($level['title']) ?><?= $this->endSection() ?>

<?= $this->section('head') ?>
<link rel="stylesheet" href="<?= base_url('vendor/codemirror/lib/codemirror.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container ch-<?= $meta['color'] ?>" id="boss">
    <div class="boss-head">
        <a class="quiz-close" href="<?= site_url('level/' . $level['id']) ?>" title="Keluar (draf tersimpan otomatis)"><?= icon('arrow-left') ?></a>
        <span class="badge badge-boss"><?= icon($level['type'] === 'final_boss' ? 'crown' : 'sword') ?> <?= esc(level_type_label($level['type'])) ?></span>
        <h1>Level <?= (int) $level['global_no'] ?>: <?= esc($level['title']) ?></h1>
        <div class="boss-meta">
            <span class="hearts" data-hearts-live></span>
            <span class="fail-dots" data-fail-dots title="Submit gagal di siklus ini"></span>
            <?php if ($page['is_replay']): ?><span class="badge badge-ok">Mode ulang</span><?php endif ?>
        </div>
    </div>

    <section class="mission">
        <h2><?= icon('target') ?> Misi (variasi <?= (int) $challenge['variant'] ?>)</h2>
        <div><?= format_text($challenge['instruction']) ?></div>
    </section>

    <div class="tabs" role="tablist">
        <button class="btn btn-sm is-active" type="button" data-tab="editor"><?= icon('code') ?> Kode</button>
        <button class="btn btn-sm" type="button" data-tab="output"><?= icon('eye') ?> Hasil</button>
    </div>

    <div class="sandbox">
        <section class="pane" data-pane="editor">
            <div class="pane-head">
                <?= icon('code') ?> <?= esc($langLabel) ?>
                <span class="spacer"></span>
                <span class="save-state" data-save-state></span>
                <button class="btn btn-ghost btn-sm" type="button" data-reset title="Kembalikan ke kode awal"><?= icon('undo') ?> Reset</button>
            </div>
            <div class="pane-body">
                <div class="editor-host"><textarea class="editor-fallback" data-editor spellcheck="false" aria-label="Editor kode"></textarea></div>
            </div>
            <div class="action-bar">
                <button class="btn" type="button" data-run><?= icon('play') ?> Run</button>
                <button class="btn btn-boss" type="button" data-submit><?= icon('send') ?> Submit</button>
                <span class="small muted">
                    <?= $boot['runner'] === 'iframe' ? 'Preview otomatis saat mengetik · Ctrl+Enter = Run' : 'Ctrl+Enter = Run untuk melihat hasil' ?>
                </span>
            </div>
        </section>

        <section class="pane is-hidden-mobile" data-pane="output">
            <div class="pane-head"><?= icon('terminal') ?> Hasil <span class="spacer"></span><span class="small muted" data-run-state></span></div>
            <div class="pane-body" data-output></div>
            <ul class="tests" data-tests aria-label="Test case"></ul>
        </section>
    </div>

    <section class="ai-box hidden" data-ai>
        <h3><?= icon('sparkle') ?> AI Tutor</h3>
        <div data-ai-body></div>
    </section>
</div>

<div class="modal hidden" data-modal role="dialog" aria-modal="true">
    <div class="modal-card" data-modal-body></div>
</div>

<?= json_script('boss-boot', $boot + ['kodiUrl' => base_url('assets/img/kodi.svg'), 'libraryUrl' => site_url('perpustakaan?level=' . $level['id'])]) ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('vendor/codemirror/lib/codemirror.js') ?>"></script>
<?php foreach (['xml', 'javascript', 'css', 'htmlmixed', 'clike', 'php', 'sql'] as $mode): ?>
<script src="<?= base_url('vendor/codemirror/mode/' . $mode . '/' . $mode . '.js') ?>"></script>
<?php endforeach ?>
<script src="<?= base_url('vendor/codemirror/addon/edit/closebrackets.js') ?>"></script>
<script src="<?= base_url('vendor/codemirror/addon/edit/matchbrackets.js') ?>"></script>
<script src="<?= base_url('vendor/codemirror/addon/fold/xml-fold.js') ?>"></script>
<script src="<?= base_url('vendor/codemirror/addon/edit/closetag.js') ?>"></script>
<script type="module" src="<?= base_url('assets/js/boss.js') ?>"></script>
<?= $this->endSection() ?>
