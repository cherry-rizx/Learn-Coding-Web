<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LevelModel;
use App\Models\QuestionModel;
use App\Models\QuestionOptionModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use RuntimeException;
use Throwable;

class Questions extends BaseController
{
    public function bank()
    {
        $builder = db_connect()->table('questions q')
            ->select('q.id, q.type, q.prompt, q.level_id, l.level_no, c.name AS chapter, c.order_no')
            ->join('levels l', 'l.id = q.level_id')
            ->join('chapters c', 'c.id = l.chapter_id');

        $levelId = (int) $this->request->getGet('level');
        $type    = (string) $this->request->getGet('type');
        $search  = trim((string) $this->request->getGet('q'));

        if ($levelId > 0) {
            $builder->where('q.level_id', $levelId);
        }

        if (in_array($type, ['mcq', 'arrange'], true)) {
            $builder->where('q.type', $type);
        }

        if ($search !== '') {
            $builder->like('q.prompt', $search);
        }

        return $this->render('admin/bank', [
            'rows'    => $builder->orderBy('c.order_no')->orderBy('l.level_no')->orderBy('q.id')->get()->getResultArray(),
            'filters' => ['level' => $levelId, 'type' => $type, 'q' => $search],
            'levels'  => db_connect()->table('levels l')
                ->select('l.id, l.level_no, l.title, c.name AS chapter, c.order_no')
                ->join('chapters c', 'c.id = l.chapter_id')
                ->orderBy('c.order_no')->orderBy('l.level_no')->get()->getResultArray(),
        ]);
    }

    public function create(int $levelId)
    {
        $level = $this->quizLevel($levelId);
        $type  = $this->request->getGet('type') === 'arrange' ? 'arrange' : 'mcq';

        return $this->render('admin/question_form', [
            'level'    => $level,
            'question' => ['type' => $type, 'prompt' => '', 'clue' => '', 'explanation' => ''],
            'form'     => ['items' => ['', '', '', ''], 'distractors' => ['', ''], 'correct' => 0],
            'action'   => site_url('admin/level/' . $levelId . '/soal'),
        ]);
    }

    public function store(int $levelId)
    {
        $level = $this->quizLevel($levelId);
        $type  = $this->postedType();

        if (! $this->validateQuestionText()) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $options = $this->optionRows($type);

        if (isset($options['error'])) {
            return redirect()->back()->withInput()->with('errors', [$options['error']]);
        }

        $this->saveQuestion(null, $levelId, $type, $options['rows']);

        return redirect()->to('admin/level/' . $levelId)->with('message', 'Soal berhasil ditambahkan.');
    }

    public function edit(int $questionId)
    {
        $question = model(QuestionModel::class)->find($questionId)
            ?? throw PageNotFoundException::forPageNotFound('Soal tidak ditemukan.');
        $level = $this->quizLevel((int) $question['level_id']);
        $options = model(QuestionOptionModel::class)
            ->where('question_id', $questionId)
            ->orderBy('id')
            ->findAll();

        if ($question['type'] === 'mcq') {
            $items   = array_values(array_column($options, 'content'));
            $correct = 0;
            foreach ($options as $index => $option) {
                if ((int) $option['is_correct'] === 1) {
                    $correct = $index;
                    break;
                }
            }
            $distractors = [];
        } else {
            $ordered     = array_values(array_filter($options, static fn (array $o): bool => $o['correct_position'] !== null));
            usort($ordered, static fn (array $a, array $b): int => (int) $a['correct_position'] <=> (int) $b['correct_position']);
            $items       = array_column($ordered, 'content');
            $distractors = array_column(array_filter($options, static fn (array $o): bool => $o['correct_position'] === null), 'content');
            $correct     = 0;
        }

        return $this->render('admin/question_form', [
            'level'    => $level,
            'question' => $question,
            'form'     => ['items' => $items, 'distractors' => $distractors, 'correct' => $correct],
            'action'   => site_url('admin/soal/' . $questionId),
        ]);
    }

    public function update(int $questionId)
    {
        $question = model(QuestionModel::class)->find($questionId)
            ?? throw PageNotFoundException::forPageNotFound('Soal tidak ditemukan.');
        $levelId = (int) $question['level_id'];
        $this->quizLevel($levelId);
        $type    = $question['type']; // Tipe soal tidak dapat diubah lewat hidden input.

        if (! $this->validateQuestionText()) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $options = $this->optionRows($type);

        if (isset($options['error'])) {
            return redirect()->back()->withInput()->with('errors', [$options['error']]);
        }

        $this->saveQuestion($questionId, $levelId, $type, $options['rows']);

        return redirect()->to('admin/level/' . $levelId)->with('message', 'Soal berhasil diperbarui. Sesi kuis aktif di level ini ditutup agar perubahan konten tidak mengubah soal yang sedang dikerjakan.');
    }

    public function delete(int $questionId)
    {
        $question = model(QuestionModel::class)->find($questionId)
            ?? throw PageNotFoundException::forPageNotFound('Soal tidak ditemukan.');
        $levelId = (int) $question['level_id'];
        $this->quizLevel($levelId);

        $db = db_connect();
        $db->transBegin();
        $db->table('level_attempts')->where(['level_id' => $levelId, 'status' => 'active'])
            ->update(['status' => 'failed', 'finished_at' => date('Y-m-d H:i:s')]);
        model(QuestionModel::class)->delete($questionId);
        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new RuntimeException('Soal tidak berhasil dihapus.');
        }

        return redirect()->to('admin/level/' . $levelId)->with('message', 'Soal berhasil dihapus dan sesi kuis aktif di level ini ditutup.');
    }

    public function duplicate(int $questionId)
    {
        $question = model(QuestionModel::class)->find($questionId)
            ?? throw PageNotFoundException::forPageNotFound('Soal tidak ditemukan.');
        $levelId = (int) $question['level_id'];
        $this->quizLevel($levelId);

        $db = db_connect();
        $db->transBegin();

        try {
            $copy = $question;
            unset($copy['id'], $copy['created_at'], $copy['updated_at']);
            $copyId = model(QuestionModel::class)->insert($copy);

            if ($copyId === false) {
                throw new RuntimeException('Soal tidak berhasil diduplikasi.');
            }

            $options = model(QuestionOptionModel::class)
                ->where('question_id', $questionId)
                ->orderBy('id')
                ->findAll();
            $optionModel = model(QuestionOptionModel::class);

            foreach ($options as $option) {
                unset($option['id'], $option['created_at'], $option['updated_at']);
                $option['question_id'] = (int) $copyId;
                $optionModel->insert($option);
            }

            if ($db->transStatus() === false) {
                throw new RuntimeException('Soal tidak berhasil diduplikasi.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }

        return redirect()->to('admin/level/' . $levelId)->with('message', 'Soal berhasil diduplikasi.');
    }

    private function quizLevel(int $levelId): array
    {
        $level = model(LevelModel::class)->find($levelId)
            ?? throw PageNotFoundException::forPageNotFound('Level tidak ditemukan.');

        if ($level['type'] !== 'quiz') {
            throw PageNotFoundException::forPageNotFound('Soal hanya tersedia untuk level kuis.');
        }

        return $level;
    }

    private function postedType(): string
    {
        return $this->request->getPost('type') === 'arrange' ? 'arrange' : 'mcq';
    }

    private function validateQuestionText(): bool
    {
        return $this->validate([
            'prompt'      => 'required|max_length[10000]',
            'clue'        => 'required|max_length[5000]',
            'explanation' => 'required|max_length[5000]',
        ]);
    }

    /** @return array{rows?: list<array{content: string, is_correct: int, correct_position: int|null}>, error?: string} */
    private function optionRows(string $type): array
    {
        $values = $this->request->getPost('options');
        $values = is_array($values) ? $values : [];
        $items  = [];

        foreach ($values as $index => $value) {
            if (is_string($value) && trim($value) !== '') {
                $items[$index] = trim($value);
            }
        }

        if ($type === 'mcq') {
            if (count($items) < 2 || count($items) > 30) {
                return ['error' => 'Pilihan ganda harus memiliki 2 sampai 30 opsi terisi.'];
            }

            $correct = filter_var($this->request->getPost('correct'), FILTER_VALIDATE_INT);

            if ($correct === false || ! array_key_exists($correct, $items)) {
                return ['error' => 'Pilih satu jawaban yang benar.'];
            }

            $rows = [];
            foreach ($items as $index => $content) {
                $rows[] = ['content' => $content, 'is_correct' => (int) ($index === $correct), 'correct_position' => null];
            }

            return ['rows' => $rows];
        }

        $distractorValues = $this->request->getPost('distractors');
        $distractorValues = is_array($distractorValues) ? $distractorValues : [];
        $distractors = array_values(array_filter(
            array_map(static fn ($value): string => is_string($value) ? trim($value) : '', $distractorValues),
            static fn (string $value): bool => $value !== '',
        ));

        if (count($items) < 2 || count($items) + count($distractors) > 30) {
            return ['error' => 'Susun kata harus memiliki minimal 2 potongan dan total maksimal 30 opsi.'];
        }

        $rows = [];
        foreach (array_values($items) as $index => $content) {
            $rows[] = ['content' => $content, 'is_correct' => 0, 'correct_position' => $index + 1];
        }
        foreach ($distractors as $content) {
            $rows[] = ['content' => $content, 'is_correct' => 0, 'correct_position' => null];
        }

        return ['rows' => $rows];
    }

    /** @param list<array{content: string, is_correct: int, correct_position: int|null}> $options */
    private function saveQuestion(?int $questionId, int $levelId, string $type, array $options): int
    {
        $db             = db_connect();
        $isUpdate       = $questionId !== null;
        $questionModel = model(QuestionModel::class);
        $optionModel   = model(QuestionOptionModel::class);
        $data           = [
            'level_id'    => $levelId,
            'type'        => $type,
            'prompt'      => trim((string) $this->request->getPost('prompt')),
            'clue'        => trim((string) $this->request->getPost('clue')),
            'explanation' => trim((string) $this->request->getPost('explanation')),
        ];

        $db->transBegin();

        try {
            if ($questionId === null) {
                $questionId = $questionModel->insert($data);
                if ($questionId === false) {
                    throw new RuntimeException('Soal tidak berhasil disimpan.');
                }
            } else {
                $questionModel->update($questionId, $data);
            }

            if ($isUpdate && $questionId !== null) {
                $db->table('level_attempts')->where(['level_id' => $levelId, 'status' => 'active'])
                    ->update(['status' => 'failed', 'finished_at' => date('Y-m-d H:i:s')]);
            }

            if ($questionId !== null) {
                $optionModel->where('question_id', $questionId)->delete();
            }

            foreach ($options as $option) {
                $optionModel->insert($option + ['question_id' => $questionId]);
            }

            if ($db->transStatus() === false) {
                throw new RuntimeException('Perubahan soal tidak berhasil disimpan.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }

        return (int) $questionId;
    }
}
