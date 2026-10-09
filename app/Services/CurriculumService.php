<?php

namespace App\Services;

use App\Models\ChapterModel;
use App\Models\LevelModel;
use App\Models\UserLevelProgressModel;
use Config\Game;

class CurriculumService
{
    private Game $config;

    public function __construct(?Game $config = null)
    {
        $this->config = $config ?? config(Game::class);
    }

    // Peta 5 bab x 10 level beserta status kunci & bintang milik user
    public function map(int $userId): array
    {
        $chapters = model(ChapterModel::class)->orderBy('order_no')->findAll();
        $levels   = model(LevelModel::class)->orderBy('level_no')->findAll();
        $progress = array_column(
            model(UserLevelProgressModel::class)->where('user_id', $userId)->findAll(),
            null,
            'level_id',
        );
        $content = $this->contentCounts();

        $byChapter = [];

        foreach ($levels as $level) {
            $byChapter[$level['chapter_id']][] = $level;
        }

        $result          = [];
        $chapterUnlocked = true; // bab 1 selalu terbuka
        $currentFound    = false;

        foreach ($chapters as $chapter) {
            $prevPassed   = true;
            $chapterStars = 0;
            $finalPassed  = false;
            $items        = [];

            foreach ($byChapter[$chapter['id']] ?? [] as $level) {
                $best     = (int) ($progress[$level['id']]['best_stars'] ?? 0);
                $unlocked = $chapterUnlocked && $prevPassed;
                $state    = match (true) {
                    ! $unlocked => 'locked',
                    $best >= 2  => 'passed',
                    $best === 1 => 'failed',
                    default     => 'open',
                };

                $isCurrent = false;

                if (! $currentFound && in_array($state, ['open', 'failed'], true)) {
                    $isCurrent    = true; // level berikutnya yang harus dikerjakan
                    $currentFound = true;
                }

                $items[] = $level + [
                    'global_no'   => LevelModel::globalNumber((int) $chapter['order_no'], (int) $level['level_no']),
                    'best_stars'  => $best,
                    'state'       => $state,
                    'unlocked'    => $unlocked,
                    'is_current'  => $isCurrent,
                    'has_content' => $this->hasContent($level, $content),
                ];

                if ($best >= 2) {
                    $chapterStars += $best;
                }

                if ((int) $level['level_no'] === 10) {
                    $finalPassed = $best >= 2;
                }

                $prevPassed = $best >= 2;
            }

            $result[] = $chapter + [
                'unlocked'     => $chapterUnlocked,
                'stars'        => $chapterStars,
                'max_stars'    => count($items) * 3,
                'passed_count' => count(array_filter($items, static fn ($l) => $l['state'] === 'passed')),
                'final_passed' => $finalPassed,
                'levels'       => $items,
            ];

            // Syarat buka bab berikutnya: final boss lulus + minimal 24 bintang
            $chapterUnlocked = $chapterUnlocked && $finalPassed && $chapterStars >= $this->config->chapterUnlockStars;
        }

        return $result;
    }

    // Satu level lengkap dengan bab dan statusnya, null kalau tidak ada
    public function findLevel(int $userId, int $levelId): ?array
    {
        foreach ($this->map($userId) as $chapter) {
            foreach ($chapter['levels'] as $level) {
                if ((int) $level['id'] === $levelId) {
                    unset($chapter['levels']);

                    return ['level' => $level, 'chapter' => $chapter];
                }
            }
        }

        return null;
    }

    // Level setelah $levelId kalau sudah terbuka, untuk tombol "Lanjut"
    public function nextLevel(int $userId, int $levelId): ?array
    {
        $flat = [];

        foreach ($this->map($userId) as $chapter) {
            array_push($flat, ...$chapter['levels']);
        }

        foreach ($flat as $i => $level) {
            if ((int) $level['id'] === $levelId) {
                $next = $flat[$i + 1] ?? null;

                return $next !== null && $next['unlocked'] ? $next : null;
            }
        }

        return null;
    }

    // ID level yang sudah terbuka, untuk perpustakaan materi
    public function unlockedLevelIds(int $userId): array
    {
        $ids = [];

        foreach ($this->map($userId) as $chapter) {
            foreach ($chapter['levels'] as $level) {
                if ($level['unlocked']) {
                    $ids[] = (int) $level['id'];
                }
            }
        }

        return $ids;
    }

    public function summary(array $map): array
    {
        $passed = 0;

        foreach ($map as $chapter) {
            $passed += $chapter['passed_count'];
        }

        return [
            'stars'         => array_sum(array_column($map, 'stars')),
            'max_stars'     => array_sum(array_column($map, 'max_stars')),
            'passed_levels' => $passed,
        ];
    }

    // Jumlah soal & variasi tantangan per level, untuk label "segera hadir"
    private function contentCounts(): array
    {
        $db        = db_connect();
        $questions = array_column(
            $db->table('questions')->select('level_id, COUNT(*) AS total')->groupBy('level_id')->get()->getResultArray(),
            'total',
            'level_id',
        );
        $challenges = array_column(
            $db->table('challenges')->select('level_id, COUNT(*) AS total')->groupBy('level_id')->get()->getResultArray(),
            'total',
            'level_id',
        );

        return ['questions' => $questions, 'challenges' => $challenges];
    }

    private function hasContent(array $level, array $content): bool
    {
        if ($level['type'] === 'quiz') {
            return (int) ($content['questions'][$level['id']] ?? 0) >= $this->config->questionsPerQuiz;
        }

        return (int) ($content['challenges'][$level['id']] ?? 0) > 0;
    }
}
