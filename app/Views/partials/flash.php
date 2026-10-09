<?php if (session('message') !== null): ?>
    <div class="alert alert-success" role="status"><?= icon('check') ?> <div><?= esc(session('message')) ?></div></div>
<?php endif ?>

<?php if (session('error') !== null): ?>
    <div class="alert alert-error" role="alert"><?= icon('alert') ?> <div><?= esc(session('error')) ?></div></div>
<?php endif ?>

<?php if (is_array(session('errors')) && session('errors') !== []): ?>
    <div class="alert alert-error" role="alert">
        <?= icon('alert') ?>
        <ul>
            <?php foreach (session('errors') as $err): ?>
                <li><?= esc($err) ?></li>
            <?php endforeach ?>
        </ul>
    </div>
<?php endif ?>
