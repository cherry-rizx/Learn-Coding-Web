<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\LevelAttemptModel;
use App\Services\CurriculumService;
use App\Services\QuizService;

class Quiz extends BaseController
{
    public function state(int $levelId)
    {
        [$level, $attempt, $error] = $this->context($levelId);

        if ($error !== null) {
            return $error;
        }

        return $this->response->setJSON((new QuizService())->state($attempt, $level));
    }

    public function answer(int $levelId)
    {
        [$level, $attempt, $error] = $this->context($levelId);

        if ($error !== null) {
            return $error;
        }

        $answer = $this->request->getJSON(true)['answer'] ?? null;

        // mcq: satu id, arrange: daftar id (maks 30 potongan)
        if (! (is_int($answer) || (is_array($answer) && count($answer) <= 30 && array_is_list($answer)))) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'invalid_answer']);
        }

        $quiz   = new QuizService();
        $result = $quiz->answer($attempt, $level, $answer);

        if (isset($result['error'])) {
            return $this->response->setStatusCode(409)->setJSON($result);
        }

        // Key "finished" hanya ada saat soal selesai (maju ke soal berikutnya)
        if (array_key_exists('finished', $result)) {
            if ($result['finished'] !== null) {
                $next                        = (new CurriculumService())->nextLevel($this->userId(), $levelId);
                $result['finished']['next']  = $next === null ? null : site_url('level/' . $next['id']);
                $result['finished']['retry'] = site_url('level/' . $levelId);
            } else {
                $fresh           = model(LevelAttemptModel::class)->find($attempt['id']);
                $result['state'] = $quiz->state($fresh, $level);
            }
        }

        return $this->response->setJSON($result);
    }

    // Level terbuka + sesi aktif milik user ini
    private function context(int $levelId): array
    {
        $found = $this->unlockedLevel($levelId);

        if ($found === null || $found['level']['type'] !== 'quiz') {
            return [null, null, $this->response->setStatusCode(403)->setJSON(['error' => 'locked'])];
        }

        $attempt = model(LevelAttemptModel::class)->active($this->userId(), $levelId);

        if ($attempt === null) {
            return [null, null, $this->response->setStatusCode(404)->setJSON(['error' => 'no_attempt'])];
        }

        return [$found['level'], $attempt, null];
    }
}
