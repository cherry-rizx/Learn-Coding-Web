<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\LevelAttemptModel;
use App\Services\AiTutorService;
use App\Services\BossService;
use App\Services\CurriculumService;

class Boss extends BaseController
{
    // Autosave draf kode di editor
    public function draft(int $levelId)
    {
        [, $attempt, $error] = $this->context($levelId);

        if ($error !== null) {
            return $error;
        }

        $code = $this->code();

        if ($code === null) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'invalid_code']);
        }

        (new BossService())->saveDraft($attempt, $code);

        return $this->response->setJSON(['ok' => true]);
    }

    public function submit(int $levelId)
    {
        [$level, $attempt, $error] = $this->context($levelId);

        if ($error !== null) {
            return $error;
        }

        $code   = $this->code();
        $passed = $this->request->getJSON(true)['passed'] ?? null;

        if ($code === null || ! is_bool($passed)) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'invalid_submit']);
        }

        $result = (new BossService())->submit($attempt, $level, $code, $passed);

        if (isset($result['error'])) {
            return $this->response->setStatusCode(409)->setJSON($result);
        }

        if ($result['passed']) {
            $next            = (new CurriculumService())->nextLevel($this->userId(), $levelId);
            $result['next']  = $next === null ? null : site_url('level/' . $next['id']);
        }

        return $this->response->setJSON($result);
    }

    // Petunjuk AI. Dipanggil lewat server, tidak pernah langsung dari JS ke 9Router
    public function hint(int $levelId)
    {
        [, $attempt, $error] = $this->context($levelId);

        if ($error !== null) {
            return $error;
        }

        $json    = $this->request->getJSON(true) ?? [];
        $trigger = ($json['trigger'] ?? 'manual') === 'auto' ? 'auto' : 'manual';
        $summary = mb_substr(trim((string) ($json['summary'] ?? '')), 0, 1500);

        $result = (new BossService())->hint($attempt, $trigger, $summary, new AiTutorService());

        if (isset($result['error'])) {
            return $this->response->setStatusCode(409)->setJSON($result);
        }

        return $this->response->setJSON($result);
    }

    private function context(int $levelId): array
    {
        $found = $this->unlockedLevel($levelId);

        if ($found === null || $found['level']['type'] === 'quiz') {
            return [null, null, $this->response->setStatusCode(403)->setJSON(['error' => 'locked'])];
        }

        $attempt = model(LevelAttemptModel::class)->active($this->userId(), $levelId);

        if ($attempt === null) {
            return [null, null, $this->response->setStatusCode(404)->setJSON(['error' => 'no_attempt'])];
        }

        return [$found['level'], $attempt, null];
    }

    // Kode dari body JSON, null kalau bukan string atau terlalu besar
    private function code(): ?string
    {
        $code = $this->request->getJSON(true)['code'] ?? null;

        if (! is_string($code) || strlen($code) > config('Game')->draftMaxBytes) {
            return null;
        }

        return $code;
    }
}
