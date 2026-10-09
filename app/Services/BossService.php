<?php

namespace App\Services;

use App\Models\AiHintModel;
use App\Models\ChallengeModel;
use App\Models\LevelAttemptModel;
use App\Models\SubmissionModel;
use Config\Game;

/**
 * Alur level boss: satu attempt = satu siklus sampai lulus atau gagal 4 kali.
 * Test case dijalankan di browser (keterbatasan yang disadari).
 */
class BossService
{
    public const FALLBACK = 'Petunjuk sedang tidak tersedia. Coba baca lagi instruksi dan hasil test case di bawah editor.';

    private Game $config;
    private ProgressService $progress;
    private LevelAttemptModel $attempts;
    private ChallengeModel $challenges;

    public function __construct(?ProgressService $progress = null)
    {
        $this->config     = config(Game::class);
        $this->progress   = $progress ?? new ProgressService();
        $this->attempts   = model(LevelAttemptModel::class);
        $this->challenges = model(ChallengeModel::class);
    }

    public function start(int $userId, array $level): array
    {
        $progress = $this->progress->getProgress($userId, (int) $level['id']);
        $active   = $this->attempts->active($userId, (int) $level['id']);

        if ($active !== null && $active['challenge_id'] !== null) {
            return ['attempt' => $active];
        }

        if ($active !== null) {
            // Variasi dihapus saat siklus berjalan: tutup siklus lama
            $this->attempts->update($active['id'], ['status' => 'failed', 'finished_at' => date('Y-m-d H:i:s')]);
        }

        $isReplay = $this->progress->isReplay($progress);
        $hearts   = $this->progress->heartsStatus($progress);

        if (! $isReplay && $hearts['hearts'] === 0) {
            return ['blocked' => $hearts];
        }

        // Siklus baru memakai variasi berbeda dari siklus terakhir
        $last      = $this->attempts->lastFinished($userId, (int) $level['id']);
        $challenge = $this->challenges->pickVariant((int) $level['id'], $last['challenge_id'] ?? null);

        if ($challenge === null) {
            return ['empty' => true];
        }

        $id = $this->attempts->insert([
            'user_id'      => $userId,
            'level_id'     => $level['id'],
            'challenge_id' => $challenge['id'],
            'status'       => 'active',
            'is_replay'    => $isReplay ? 1 : 0,
            'fail_count'   => 0,
            'draft_code'   => $challenge['starter_code'],
        ]);

        return ['attempt' => $this->attempts->find($id)];
    }

    // Data halaman boss, tanpa solution_code
    public function pageData(array $attempt, array $level): array
    {
        $challenge = $this->challenges->forBrowser((int) $attempt['challenge_id']);
        $progress  = $this->progress->getProgress((int) $attempt['user_id'], (int) $level['id']);

        return [
            'challenge' => [
                'id'          => (int) $challenge['id'],
                'variant'     => (int) $challenge['variant_no'],
                'instruction' => $challenge['instruction'],
                'starter'     => (string) $challenge['starter_code'],
                'setup'       => (string) $challenge['setup_code'],
                'tests'       => json_decode((string) $challenge['test_cases'], true) ?: [],
            ],
            'draft'      => $attempt['draft_code'] ?? $challenge['starter_code'],
            'fail_count' => (int) $attempt['fail_count'],
            'max_fails'  => $this->config->bossMaxFails,
            'is_replay'  => (int) $attempt['is_replay'] === 1,
            'hearts'     => $this->progress->heartsStatus($progress),
            'heart_cost' => $this->config->heartCost[$level['type']] ?? 1,
            'hint'       => $this->hintAvailability($attempt),
        ];
    }

    public function saveDraft(array $attempt, string $code): void
    {
        $this->attempts->update($attempt['id'], ['draft_code' => $code]);
    }

    public function submit(array $attempt, array $level, string $code, bool $passed): array
    {
        $progress = $this->progress->getProgress((int) $attempt['user_id'], (int) $level['id']);
        $isReplay = (int) $attempt['is_replay'] === 1;

        if (! $isReplay && $this->progress->heartsStatus($progress)['hearts'] === 0) {
            return ['error' => 'no_hearts', 'hearts' => $this->progress->heartsStatus($progress)];
        }

        $submissionId = model(SubmissionModel::class)->insert([
            'attempt_id' => $attempt['id'],
            'code'       => $code,
            'passed'     => $passed ? 1 : 0,
        ]);
        $failCount = (int) $attempt['fail_count'];

        if ($passed) {
            $stars = $this->progress->bossStars($failCount + 1);

            $this->attempts->update($attempt['id'], [
                'status'       => 'passed',
                'draft_code'   => $code,
                'stars_earned' => $stars,
                'finished_at'  => date('Y-m-d H:i:s'),
            ]);
            $this->progress->recordStars((int) $attempt['user_id'], (int) $level['id'], $stars);

            return ['passed' => true, 'stars' => $stars, 'submit_no' => $failCount + 1];
        }

        $failCount++;

        if (! $isReplay) {
            $progress = $this->progress->loseHearts($progress, $level['type']);
        }

        $cycleOver = $this->progress->bossCycleFailed($failCount);
        $data      = ['fail_count' => $failCount, 'draft_code' => $code];

        if ($cycleOver) {
            // Gagal 4 kali: 1 bintang, siklus berikutnya mulai dari nol dengan variasi lain
            $data += ['status' => 'failed', 'stars_earned' => 1, 'finished_at' => date('Y-m-d H:i:s')];
            $this->progress->recordStars((int) $attempt['user_id'], (int) $level['id'], 1);
        }

        $this->attempts->update($attempt['id'], $data);

        return [
            'passed'        => false,
            'submission_id' => $submissionId,
            'fail_count'    => $failCount,
            'max_fails'     => $this->config->bossMaxFails,
            'cycle_over'    => $cycleOver,
            'hearts'        => $this->progress->heartsStatus($progress),
            'hearts_lost'   => $isReplay ? 0 : ($this->config->heartCost[$level['type']] ?? 1),
            'hint'          => $cycleOver ? null : $this->hintAvailability($attempt + ['fail_count' => $failCount], $failCount),
        ];
    }

    // Gagal ke-1: tombol manual. Gagal ke-2 dan ke-3: penjelasan otomatis
    public function hintAvailability(array $attempt, ?int $failCount = null): ?string
    {
        $failCount ??= (int) $attempt['fail_count'];

        return match (true) {
            $failCount === 1 => 'manual',
            $failCount >= 2  => 'auto',
            default          => null,
        };
    }

    public function hint(array $attempt, string $trigger, string $summary, AiTutorService $ai): array
    {
        $allowed = $this->hintAvailability($attempt);

        if ($allowed === null || ($trigger === 'auto' && $allowed !== 'auto')) {
            return ['error' => 'not_allowed'];
        }

        $submission = model(SubmissionModel::class)
            ->where('attempt_id', $attempt['id'])
            ->orderBy('id', 'DESC')
            ->first();

        if ($submission === null || (int) $submission['passed'] === 1) {
            return ['error' => 'not_allowed'];
        }

        $hints    = model(AiHintModel::class);
        $existing = $hints->where('submission_id', $submission['id'])->first();

        if ($existing !== null && $existing['status'] === 'ok') {
            // Satu petunjuk per submit, supaya tidak bisa memancing AI berulang-ulang
            return ['text' => $existing['response'], 'status' => 'ok', 'trigger' => $existing['trigger_type']];
        }

        $challenge = $this->challenges->find((int) $attempt['challenge_id']); // termasuk solution_code, hanya di server
        $text      = $ai->hint($challenge, (string) $submission['code'], $summary, $trigger);
        $status    = $text === null ? 'fallback' : 'ok';
        $row       = ['trigger_type' => $trigger, 'response' => $text, 'status' => $status];

        if ($existing !== null) {
            $hints->update($existing['id'], $row); // sebelumnya fallback: boleh dicoba lagi
        } else {
            $hints->insert($row + ['attempt_id' => $attempt['id'], 'submission_id' => $submission['id']]);
        }

        return ['text' => $text ?? self::FALLBACK, 'status' => $status, 'trigger' => $trigger];
    }
}
