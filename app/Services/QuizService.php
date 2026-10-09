<?php

namespace App\Services;

use App\Models\AttemptAnswerModel;
use App\Models\LevelAttemptModel;
use App\Models\QuestionModel;
use App\Models\QuestionOptionModel;
use Config\Game;

/**
 * Alur kuis level biasa. Semua penilaian terjadi di server:
 * browser hanya menerima teks soal dan pilihan tanpa kunci jawaban.
 */
class QuizService
{
    private Game $config;
    private ProgressService $progress;
    private LevelAttemptModel $attempts;
    private QuestionModel $questions;
    private QuestionOptionModel $options;

    public function __construct(?ProgressService $progress = null)
    {
        $this->config    = config(Game::class);
        $this->progress  = $progress ?? new ProgressService();
        $this->attempts  = model(LevelAttemptModel::class);
        $this->questions = model(QuestionModel::class);
        $this->options   = model(QuestionOptionModel::class);
    }

    // Lanjutkan sesi aktif atau buat sesi baru dengan 10 soal acak
    public function start(int $userId, array $level): array
    {
        $progress = $this->progress->getProgress($userId, (int) $level['id']);
        $active   = $this->attempts->active($userId, (int) $level['id']);

        if ($active !== null) {
            return ['attempt' => $active];
        }

        $isReplay = $this->progress->isReplay($progress);
        $hearts   = $this->progress->heartsStatus($progress);

        if (! $isReplay && $hearts['hearts'] === 0) {
            return ['blocked' => $hearts];
        }

        $ids = $this->questions->where('level_id', $level['id'])->findColumn('id') ?? [];

        if (count($ids) < $this->config->questionsPerQuiz) {
            return ['empty' => true];
        }

        shuffle($ids);
        $order = array_map('intval', array_slice($ids, 0, $this->config->questionsPerQuiz));

        $id = $this->attempts->insert([
            'user_id'        => $userId,
            'level_id'       => $level['id'],
            'status'         => 'active',
            'is_replay'      => $isReplay ? 1 : 0,
            'question_order' => json_encode($order), // urutan tetap walau refresh
            'current_index'  => 0,
            'correct_count'  => 0,
        ]);

        return ['attempt' => $this->attempts->find($id)];
    }

    // Data soal saat ini untuk browser (tanpa kunci jawaban)
    public function state(array $attempt, array $level): array
    {
        $order = json_decode((string) $attempt['question_order'], true) ?: [];
        $index = (int) $attempt['current_index'];
        $qid   = (int) ($order[$index] ?? 0);

        $progress = $this->progress->getProgress((int) $attempt['user_id'], (int) $level['id']);
        $hearts   = $this->progress->heartsStatus($progress);
        $isReplay = (int) $attempt['is_replay'] === 1;

        $question = $this->questions->forQuiz([$qid])[0] ?? null;

        if ($question === null) {
            // Konten berubah setelah sesi dimulai: tutup sesi, minta mulai ulang
            $this->attempts->update($attempt['id'], ['status' => 'failed', 'finished_at' => date('Y-m-d H:i:s')]);

            return ['restart' => true];
        }

        $phase = $this->phase($attempt, $qid);

        $state = [
            'index'     => $index,
            'total'     => count($order),
            'correct'   => (int) $attempt['correct_count'],
            'is_replay' => $isReplay,
            'hearts'    => $hearts,
            'phase'     => $phase,
            'blocked'   => $phase === 'first' && ! $isReplay && $hearts['hearts'] === 0,
            'question'  => [
                'id'      => (int) $question['id'],
                'type'    => $question['type'],
                'prompt'  => $question['prompt'],
                'options' => array_map(
                    static fn ($o) => ['id' => (int) $o['id'], 'content' => $o['content']],
                    $this->options->forQuiz([$qid]),
                ),
            ],
        ];

        // Setelah refresh, clue / jawaban benar tetap tampil sesuai fase
        if ($phase !== 'first') {
            $full           = $this->questions->find($qid);
            $state['clue']  = $full['clue'];

            if ($phase === 'reveal') {
                $state['reveal']      = $this->correctAnswer($qid, $question['type']);
                $state['explanation'] = $full['explanation'];
            }
        }

        return $state;
    }

    // Nilai satu jawaban. $answer: id opsi (mcq) atau daftar id opsi (arrange)
    public function answer(array $attempt, array $level, mixed $answer): array
    {
        $order = json_decode((string) $attempt['question_order'], true) ?: [];
        $qid   = (int) ($order[(int) $attempt['current_index']] ?? 0);
        $full  = $this->questions->find($qid);

        if ($full === null) {
            return ['error' => 'restart'];
        }

        $phase    = $this->phase($attempt, $qid);
        $isReplay = (int) $attempt['is_replay'] === 1;
        $correct  = $this->grade($qid, $full['type'], $answer);
        $progress = $this->progress->getProgress((int) $attempt['user_id'], (int) $level['id']);

        if ($phase === 'first') {
            if (! $isReplay && $this->progress->heartsStatus($progress)['hearts'] === 0) {
                return ['error' => 'no_hearts', 'hearts' => $this->progress->heartsStatus($progress)];
            }

            // Hanya jawaban pertama yang dinilai
            model(AttemptAnswerModel::class)->insert([
                'attempt_id'  => $attempt['id'],
                'question_id' => $qid,
                'is_correct'  => $correct ? 1 : 0,
            ]);

            if ($correct) {
                $attempt['correct_count'] = (int) $attempt['correct_count'] + 1;

                return $this->advance($attempt, $level, [
                    'result'      => 'correct',
                    'explanation' => $full['explanation'],
                ]);
            }

            if (! $isReplay) {
                $progress = $this->progress->loseHearts($progress, $level['type']);
            }

            $this->setPhase((int) $attempt['id'], $qid, 'retry');

            return [
                'result'      => 'wrong',
                'clue'        => $full['clue'],
                'hearts'      => $this->progress->heartsStatus($progress),
                'hearts_lost' => $isReplay ? 0 : ($this->config->heartCost[$level['type']] ?? 1),
            ];
        }

        if ($phase === 'retry') {
            if ($correct) {
                return $this->advance($attempt, $level, [
                    'result'      => 'correct_retry',
                    'explanation' => $full['explanation'],
                ]);
            }

            $this->setPhase((int) $attempt['id'], $qid, 'reveal');

            return [
                'result'      => 'reveal',
                'reveal'      => $this->correctAnswer($qid, $full['type']),
                'explanation' => $full['explanation'],
            ];
        }

        // Fase reveal: user wajib memasukkan jawaban benar sendiri, tanpa penalti
        if ($correct) {
            return $this->advance($attempt, $level, ['result' => 'copied']);
        }

        return ['result' => 'must_match'];
    }

    private function advance(array $attempt, array $level, array $response): array
    {
        $index = (int) $attempt['current_index'] + 1;
        $total = count(json_decode((string) $attempt['question_order'], true) ?: []);
        $data  = ['current_index' => $index, 'correct_count' => (int) $attempt['correct_count']];

        $this->clearPhase((int) $attempt['id']);

        if ($index < $total) {
            $this->attempts->update($attempt['id'], $data);

            return $response + ['finished' => null];
        }

        // Soal terakhir: hitung bintang dari jawaban pertama
        $stars = $this->progress->quizStars((int) $attempt['correct_count']);
        $data += [
            'status'       => $this->progress->isPassed($stars) ? 'passed' : 'failed',
            'stars_earned' => $stars,
            'finished_at'  => date('Y-m-d H:i:s'),
        ];
        $this->attempts->update($attempt['id'], $data);
        $this->progress->recordStars((int) $attempt['user_id'], (int) $level['id'], $stars);

        return $response + ['finished' => [
            'stars'   => $stars,
            'correct' => (int) $attempt['correct_count'],
            'total'   => $total,
            'passed'  => $this->progress->isPassed($stars),
        ]];
    }

    private function grade(int $qid, string $type, mixed $answer): bool
    {
        $options = array_column($this->options->where('question_id', $qid)->findAll(), null, 'id');

        if ($type === 'mcq') {
            $id = is_numeric($answer) ? (int) $answer : 0;

            return isset($options[$id]) && (int) $options[$id]['is_correct'] === 1;
        }

        if (! is_array($answer) || $answer === []) {
            return false;
        }

        $given = [];

        foreach ($answer as $id) {
            if (! is_numeric($id) || ! isset($options[(int) $id])) {
                return false; // id asing ditolak
            }

            $given[] = trim($options[(int) $id]['content']);
        }

        // Dibandingkan per isi, supaya potongan kembar boleh ditukar
        return $given === array_map(
            static fn ($o) => trim($o['content']),
            $this->arrangeKey($options),
        );
    }

    // Jawaban benar, hanya dikirim setelah dua kali salah
    private function correctAnswer(int $qid, string $type): array
    {
        $options = array_column($this->options->where('question_id', $qid)->findAll(), null, 'id');

        if ($type === 'mcq') {
            foreach ($options as $o) {
                if ((int) $o['is_correct'] === 1) {
                    return ['ids' => [(int) $o['id']], 'text' => $o['content']];
                }
            }

            return ['ids' => [], 'text' => ''];
        }

        $key = $this->arrangeKey($options);

        return [
            'ids'  => array_map(static fn ($o) => (int) $o['id'], $key),
            'text' => implode(' ', array_map(static fn ($o) => $o['content'], $key)),
        ];
    }

    private function arrangeKey(array $options): array
    {
        $key = array_filter($options, static fn ($o) => $o['correct_position'] !== null);
        usort($key, static fn ($a, $b) => (int) $a['correct_position'] <=> (int) $b['correct_position']);

        return $key;
    }

    // Fase soal: first -> retry (clue) -> reveal (jawaban benar ditampilkan)
    private function phase(array $attempt, int $qid): string
    {
        $saved = session('quiz_phase')[$attempt['id']] ?? null;

        if ($saved !== null && (int) $saved['q'] === $qid) {
            return $saved['phase'];
        }

        // Session hilang tapi jawaban pertama sudah tercatat -> anggap fase clue
        $answered = model(AttemptAnswerModel::class)
            ->where(['attempt_id' => $attempt['id'], 'question_id' => $qid])
            ->countAllResults();

        return $answered > 0 ? 'retry' : 'first';
    }

    private function setPhase(int $attemptId, int $qid, string $phase): void
    {
        $all             = session('quiz_phase') ?? [];
        $all[$attemptId] = ['q' => $qid, 'phase' => $phase];
        session()->set('quiz_phase', $all);
    }

    private function clearPhase(int $attemptId): void
    {
        $all = session('quiz_phase') ?? [];
        unset($all[$attemptId]);
        session()->set('quiz_phase', $all);
    }
}
