<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Game extends BaseConfig
{
    public string $appName = 'Misi Koding'; // nama aplikasi di UI & sertifikat

    public int $maxHearts          = 5;
    public int $heartRefillSeconds = 300; // 5 menit, set 60 saat demo
    public array $heartCost        = [
        'quiz'       => 1,
        'mini_boss'  => 2,
        'final_boss' => 3,
    ];

    public int $questionsPerQuiz = 10;
    public int $quizPassMin      = 7; // 7-8 benar = 2 bintang
    public int $quizPerfectMin   = 9; // 9-10 benar = 3 bintang

    public int $bossMaxFails = 4; // gagal ke-4 = siklus selesai, 1 bintang

    public int $chapterUnlockStars = 24;
    public int $certificateStars   = 150;
    public int $guestRetentionDays = 30;

    public string $aiBaseUrl   = 'http://127.0.0.1:20128/v1'; // 9Router lokal
    public string $aiApiKey    = '';
    public string $aiModel     = '';
    public int $aiTimeout      = 30;
    public int $aiMaxCodeChars = 400; // batas panjang blok kode dari AI
    public int $aiHintsPerMinute = 5; // throttler endpoint AI per user

    public int $draftMaxBytes  = 20000; // batas ukuran draf/submit kode
    public int $leaderboardSize = 50;
}
