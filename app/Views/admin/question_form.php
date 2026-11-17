<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Form soal<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$isMcq  = $question['type'] === 'mcq';
$oldItems = old('options');
$oldExtras = old('distractors');
if (is_array($oldItems)) {
    $form['items'] = array_map(static fn ($value): string => is_string($value) ? $value : '', $oldItems);
}
if (is_array($oldExtras)) {
    $form['distractors'] = array_map(static fn ($value): string => is_string($value) ? $value : '', $oldExtras);
}
$oldCorrect = old('correct');
if ($oldCorrect !== null && filter_var($oldCorrect, FILTER_VALIDATE_INT) !== false) {
    $form['correct'] = (int) $oldCorrect;
}
$items  = array_pad($form['items'], max(4, count($form['items'])), '');
$extras = array_pad($form['distractors'], max(2, count($form['distractors'])), '');
?>
<div class="container container-narrow admin-page">
    <a class="admin-back" href="<?= site_url('admin/level/' . $level['id']) ?>"><?= icon('arrow-left') ?> Kembali ke level</a>
    <div class="admin-page-head"><div><h1><?= isset($question['id']) ? 'Edit soal' : 'Tambah soal' ?></h1><p class="muted"><?= $isMcq ? 'Pilihan ganda' : 'Susun kata' ?> · Level <?= (int) $level['level_no'] ?> · <?= esc($level['title']) ?></p></div></div>

<form class="card admin-question-form" method="post" action="<?= esc($action) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="type" value="<?= esc($question['type']) ?>">

    <?php if (isset($question['id'])): ?>
        <div class="alert alert-warn"><?= icon('alert') ?><div>Menyimpan perubahan akan menutup sesi kuis aktif pada level ini agar peserta tidak melanjutkan dengan konten yang berubah.</div></div>
    <?php endif ?>

    <label class="field">Pertanyaan
        <textarea class="input" name="prompt" rows="4" required><?= esc(old('prompt', $question['prompt'])) ?></textarea>
    </label>
    <label class="field">Clue (muncul setelah salah)
        <textarea class="input" name="clue" rows="3" required><?= esc(old('clue', $question['clue'])) ?></textarea>
    </label>
    <label class="field">Penjelasan
        <textarea class="input" name="explanation" rows="3" required><?= esc(old('explanation', $question['explanation'])) ?></textarea>
    </label>

    <?php if ($isMcq): ?>
        <p class="muted">Pilih satu jawaban benar. Baris kosong akan diabaikan.</p>
        <?php foreach ($items as $i => $text): ?>
            <div class="option-row">
                <label class="correct-choice" title="Tandai sebagai jawaban benar"><input type="radio" name="correct" value="<?= $i ?>" <?= $i === $form['correct'] ? 'checked' : '' ?>><span>Benar</span></label>
                <input class="input" type="text" name="options[]" value="<?= esc($text, 'attr') ?>" aria-label="Opsi <?= $i + 1 ?>" placeholder="Opsi <?= $i + 1 ?>">
            </div>
        <?php endforeach ?>
    <?php else: ?>
        <p class="muted">Masukkan potongan jawaban dengan urutan yang benar. Baris kosong diabaikan.</p>
        <?php foreach ($items as $i => $text): ?>
            <label class="field">Potongan <?= $i + 1 ?><input class="input" type="text" name="options[]" value="<?= esc($text, 'attr') ?>" placeholder="Potongan <?= $i + 1 ?>"></label>
        <?php endforeach ?>
        <h3>Pengecoh <span class="muted small">(opsional)</span></h3>
        <?php foreach ($extras as $i => $text): ?>
            <input class="input field" type="text" name="distractors[]" value="<?= esc($text, 'attr') ?>" aria-label="Pengecoh <?= $i + 1 ?>" placeholder="Pengecoh <?= $i + 1 ?>">
        <?php endforeach ?>
    <?php endif ?>

    <div class="admin-form-actions"><a class="btn" href="<?= site_url('admin/level/' . $level['id']) ?>">Batal</a><button class="btn btn-primary" type="submit"><?= icon('check') ?> Simpan soal</button></div>
</form>
</div>
<?= $this->endSection() ?>
