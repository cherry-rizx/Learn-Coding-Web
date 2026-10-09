<?php

namespace App\Services;

use App\Models\UserLevelProgressModel;
use Config\Game;

class ProgressService
{
    private Game $config;
    private UserLevelProgressModel $progress;

    public function __construct(?Game $config = null)
    {
        $this->config   = $config ?? config(Game::class);
        $this->progress = model(UserLevelProgressModel::class);
    }

    public function quizStars(int $correct): int
    {
        if ($correct >= $this->config->quizPerfectMin) {
            return 3;
        }

        return $correct >= $this->config->quizPassMin ? 2 : 1;
    }

    // Dipanggil hanya saat submit boss lulus
    public function bossStars(int $submitNumber): int
    {
        return $submitNumber === 1 ? 3 : 2;
    }

    public function bossCycleFailed(int $failCount): bool
    {
        return $failCount >= $this->config->bossMaxFails;
    }

    public function isPassed(int $stars): bool
    {
        return $stars >= 2;
    }

    // Hearts efektif setelah isi ulang, tanpa cron
    public function currentHearts(int $left, ?string $updatedAt, ?int $now = null): array
    {
        $now ??= time();
        $max  = $this->config->maxHearts;
        $step = $this->config->heartRefillSeconds;

        if ($left >= $max || $updatedAt === null) {
            return ['hearts' => min($left, $max), 'since' => $now];
        }

        $since  = strtotime($updatedAt);
        $gained = intdiv(max(0, $now - $since), $step);
        $hearts = min($max, $left + $gained);

        // Sisa waktu menuju heart berikutnya tidak hangus
        $since = $hearts >= $max ? $now : $since + $gained * $step;

        return ['hearts' => $hearts, 'since' => $since];
    }

    public function secondsToNextHeart(int $left, ?string $updatedAt, ?int $now = null): int
    {
        $now ??= time();
        $state = $this->currentHearts($left, $updatedAt, $now);

        if ($state['hearts'] >= $this->config->maxHearts) {
            return 0;
        }

        return $this->config->heartRefillSeconds - ($now - $state['since']);
    }

    // Hasilnya langsung disimpan ke user_level_progress
    public function spendHearts(array $progress, string $levelType, ?int $now = null): array
    {
        $now ??= time();
        $state = $this->currentHearts((int) $progress['hearts_left'], $progress['hearts_updated_at'], $now);
        $cost  = $this->config->heartCost[$levelType] ?? 1;

        return [
            'hearts_left'       => max(0, $state['hearts'] - $cost),
            'hearts_updated_at' => date('Y-m-d H:i:s', $state['since']),
        ];
    }

    // Status hearts siap tampil: jumlah, maksimum, detik menuju heart berikutnya
    public function heartsStatus(array $progress, ?int $now = null): array
    {
        $now ??= time();
        $left  = (int) $progress['hearts_left'];
        $state = $this->currentHearts($left, $progress['hearts_updated_at'], $now);

        return [
            'hearts'  => $state['hearts'],
            'max'     => $this->config->maxHearts,
            'next_in' => $this->secondsToNextHeart($left, $progress['hearts_updated_at'], $now),
        ];
    }

    // Potong hearts lalu simpan, mengembalikan baris progres terbaru
    public function loseHearts(array $progress, string $levelType): array
    {
        $data = $this->spendHearts($progress, $levelType);
        $this->progress->update($progress['id'], $data);

        return array_merge($progress, $data);
    }

    // Level yang sudah lulus boleh diulang tanpa potong hearts
    public function isReplay(array $progress): bool
    {
        return $this->isPassed((int) $progress['best_stars']);
    }

    public function getProgress(int $userId, int $levelId): array
    {
        $row = $this->progress->where(['user_id' => $userId, 'level_id' => $levelId])->first();

        if ($row !== null) {
            return $row;
        }

        // Level baru: hearts penuh
        $id = $this->progress->insert([
            'user_id'     => $userId,
            'level_id'    => $levelId,
            'best_stars'  => 0,
            'hearts_left' => $this->config->maxHearts,
        ]);

        return $this->progress->find($id);
    }

    public function recordStars(int $userId, int $levelId, int $stars): void
    {
        $row = $this->getProgress($userId, $levelId);

        if ($stars <= (int) $row['best_stars']) {
            return; // pengulangan tidak menambah bintang
        }

        $data = ['best_stars' => $stars];

        if ($this->isPassed($stars) && $row['completed_at'] === null) {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }

        $this->progress->update($row['id'], $data);
        $this->refreshTotalStars($userId);
    }

    // Hanya level lulus (2-3 bintang) yang dihitung
    public function refreshTotalStars(int $userId): void
    {
        $total = (int) ($this->progress->builder()
            ->selectSum('best_stars', 'total')
            ->where('user_id', $userId)
            ->where('best_stars >=', 2)
            ->get()->getRow('total') ?? 0);

        $db      = db_connect();
        $current = (int) ($db->table('users')->select('total_stars')->where('id', $userId)->get()->getRow('total_stars') ?? 0);

        if ($total !== $current) {
            $db->table('users')->where('id', $userId)->update([
                'total_stars'      => $total,
                'stars_updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function chapterStars(int $userId, int $chapterId): int
    {
        return (int) ($this->progress->builder()
            ->selectSum('user_level_progress.best_stars', 'total')
            ->join('levels', 'levels.id = user_level_progress.level_id')
            ->where('user_level_progress.user_id', $userId)
            ->where('levels.chapter_id', $chapterId)
            ->where('user_level_progress.best_stars >=', 2)
            ->get()->getRow('total') ?? 0);
    }

    // Syarat buka bab berikutnya: final boss lulus + minimal 24 bintang
    public function nextChapterUnlocked(int $userId, int $chapterId): bool
    {
        $final = (int) ($this->progress->builder()
            ->select('user_level_progress.best_stars')
            ->join('levels', 'levels.id = user_level_progress.level_id')
            ->where('user_level_progress.user_id', $userId)
            ->where('levels.chapter_id', $chapterId)
            ->where('levels.level_no', 10)
            ->get()->getRow('best_stars') ?? 0);

        return $this->isPassed($final)
            && $this->chapterStars($userId, $chapterId) >= $this->config->chapterUnlockStars;
    }

    public function eligibleForCertificate(int $userId): bool
    {
        $row = db_connect()->table('users')->select('total_stars, is_guest')->where('id', $userId)->get()->getRow();

        return $row !== null
            && (int) $row->is_guest === 0
            && (int) $row->total_stars >= $this->config->certificateStars;
    }
}
