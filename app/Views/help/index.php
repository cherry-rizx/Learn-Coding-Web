<?php $minutes = max(1, (int) round($config->heartRefillSeconds / 60)) ?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Bantuan<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container container-narrow">
    <h1><?= icon('help') ?> Bantuan</h1>
    <p class="muted">Semua aturan main dalam satu halaman.</p>

    <div class="card faq">
        <details open>
            <summary>Bagaimana alur belajarnya?</summary>
            <p>Ada 5 bab (HTML, CSS, JavaScript, SQL, PHP) masing-masing 10 level, total 50 level berurutan. Level 1-4 dan 6-9 adalah kuis, level 5 adalah <strong>mini boss</strong>, dan level 10 adalah <strong>final boss</strong>.</p>
        </details>
        <details>
            <summary>Bagaimana kuis dinilai?</summary>
            <p>Setiap kuis berisi <?= (int) $config->questionsPerQuiz ?> soal acak (pilihan ganda atau susun kata). Hanya <strong>jawaban pertama</strong> yang dinilai:</p>
            <ul>
                <li><?= (int) $config->quizPerfectMin ?>-10 benar = 3 bintang</li>
                <li><?= (int) $config->quizPassMin ?>-<?= (int) $config->quizPerfectMin - 1 ?> benar = 2 bintang (lulus)</li>
                <li>Di bawah <?= (int) $config->quizPassMin ?> = 1 bintang (belum lulus)</li>
            </ul>
            <p>Kalau salah, kamu kehilangan heart lalu muncul clue untuk mencoba lagi. Kalau masih salah, jawaban benar ditampilkan dan kamu tinggal memasukkannya untuk lanjut (tanpa penalti).</p>
        </details>
        <details>
            <summary>Bagaimana boss dinilai?</summary>
            <p>Tulis kode di editor. <strong>Run</strong> bebas dipakai kapan saja. <strong>Submit</strong> menjalankan test case:</p>
            <ul>
                <li>Lulus di submit ke-1 = 3 bintang</li>
                <li>Lulus di submit ke-2 sampai ke-<?= (int) $config->bossMaxFails ?> = 2 bintang</li>
                <li>Gagal <?= (int) $config->bossMaxFails ?> kali = 1 bintang (belum lulus), lalu siklus baru dimulai dengan variasi tantangan yang berbeda</li>
            </ul>
            <p>Draf kodemu tersimpan otomatis, jadi aman kalau halaman tertutup.</p>
        </details>
        <details>
            <summary>Apa itu hearts?</summary>
            <p>Setiap level punya <?= (int) $config->maxHearts ?> hearts. Jawaban pertama yang salah di kuis memotong <?= (int) $config->heartCost['quiz'] ?> heart, submit gagal di mini boss <?= (int) $config->heartCost['mini_boss'] ?> hearts, dan di final boss <?= (int) $config->heartCost['final_boss'] ?> hearts.</p>
            <p>Hearts terisi lagi 1 setiap <?= $minutes ?> menit. Masuk level baru = hearts penuh. Mengulang level yang sudah lulus tidak memotong hearts. Saat hearts habis, kamu bisa membaca materi sambil menunggu.</p>
        </details>
        <details>
            <summary>Apa yang dilakukan AI tutor?</summary>
            <p>Di level boss, setelah submit gagal pertama muncul tombol <strong>Minta Petunjuk</strong> (letak kesalahan dan konsep, tanpa kode). Setelah gagal kedua, penjelasan muncul otomatis dan boleh berisi potongan kode kecil.</p>
            <p>AI tidak pernah memberi kode jawaban utuh, dan penentu lulus tetap test case, bukan AI.</p>
        </details>
        <details>
            <summary>Bagaimana membuka bab berikutnya?</summary>
            <p>Luluskan final boss bab itu <strong>dan</strong> kumpulkan minimal <?= (int) $config->chapterUnlockStars ?> dari 30 bintang di bab tersebut.</p>
        </details>
        <details>
            <summary>Leaderboard dan sertifikat?</summary>
            <p>Leaderboard menghitung total bintang terbaik per level dari level yang lulus (maksimal 150). Kalau seri, yang lebih dulu mencapai angka itu berada di atas.</p>
            <p>Sertifikat PDF ber-kode unik diberikan setelah menamatkan 50 level dengan <?= (int) $config->certificateStars ?> bintang penuh. Keasliannya bisa dicek di halaman verifikasi publik.</p>
        </details>
        <details>
            <summary>Apa bedanya akun tamu?</summary>
            <p>Akun tamu bisa langsung bermain, tapi progresnya tidak dijamin tersimpan, tidak masuk leaderboard, dan tidak bisa mendapat sertifikat. Akun tamu yang tidak aktif lebih dari <?= (int) $config->guestRetentionDays ?> hari akan dihapus.</p>
            <p>Daftar kapan saja: akun tamu di-upgrade di tempat dan semua progres ikut pindah. Kalau emailnya sudah terdaftar, progres tamu tidak bisa digabung.</p>
        </details>
        <details>
            <summary>Apakah kodeku aman dijalankan?</summary>
            <p>Kode HTML/CSS/JavaScript berjalan di iframe sandbox yang terisolasi dari situs. SQL berjalan di SQLite (sql.js) dan PHP di php-wasm, keduanya di dalam browser. Tidak ada kode yang dijalankan di server.</p>
        </details>
    </div>
</div>
<?= $this->endSection() ?>
