<?php
// $hearts = ['hearts' => n, 'max' => 5, 'next_in' => detik]
?>
<span class="hearts" data-hearts data-next-in="<?= (int) $hearts['next_in'] ?>" aria-label="<?= (int) $hearts['hearts'] ?> dari <?= (int) $hearts['max'] ?> hearts">
    <?php for ($i = 1; $i <= $hearts['max']; $i++): ?>
        <?= icon($i <= $hearts['hearts'] ? 'heart' : 'heart-outline', $i <= $hearts['hearts'] ? '' : 'is-empty') ?>
    <?php endfor ?>
    <?php if ($hearts['next_in'] > 0): ?>
        <span class="hearts-timer" title="Heart berikutnya"><?= icon('clock') ?> <span data-countdown="<?= (int) $hearts['next_in'] ?>">--:--</span></span>
    <?php endif ?>
</span>
