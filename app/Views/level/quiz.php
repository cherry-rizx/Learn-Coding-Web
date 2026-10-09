<?php $meta = chapter_meta($chapter['slug']) ?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Kuis Level <?= (int) $level['global_no'] ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container ch-<?= $meta['color'] ?>">
    <div class="quiz-shell" id="quiz">
        <div class="quiz-top">
            <a class="quiz-close" href="<?= site_url('level/' . $level['id']) ?>" title="Keluar (progres tersimpan)"><?= icon('x') ?></a>
            <div class="progress" aria-hidden="true"><span data-progress style="width:0"></span></div>
            <span class="hearts" data-hearts-live></span>
        </div>

        <div data-stage>
            <div class="center muted"><span class="spinner"></span> Memuat soal...</div>
        </div>

        <div class="quiz-footer" data-footer>
            <div class="row">
                <span class="small muted" data-counter></span>
                <button class="btn btn-primary btn-lg" type="button" data-check disabled>Periksa</button>
            </div>
        </div>
    </div>
</div>

<div class="feedback" data-feedback aria-live="polite">
    <div class="container">
        <div class="fb-icon" data-fb-icon></div>
        <div class="fb-text">
            <div class="fb-title" data-fb-title></div>
            <div data-fb-body></div>
        </div>
        <button class="btn btn-primary btn-lg" type="button" data-fb-next>Lanjut</button>
    </div>
</div>

<?= json_script('quiz-boot', $boot + ['kodiUrl' => base_url('assets/img/kodi.svg'), 'libraryUrl' => site_url('perpustakaan?level=' . $level['id'])]) ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/quiz.js') ?>"></script>
<?= $this->endSection() ?>
