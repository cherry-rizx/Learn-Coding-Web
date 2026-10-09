<?php

namespace App\Controllers;

use App\Models\LevelAttemptModel;
use App\Models\MaterialCardModel;
use App\Services\BossService;
use App\Services\ProgressService;
use App\Services\QuizService;

class Level extends BaseController
{
    // Kartu materi / ringkasan boss + status hearts sebelum mulai
    public function show(int $levelId)
    {
        $found = $this->unlockedLevel($levelId);

        if ($found === null) {
            return redirect()->to('peta')->with('error', 'Level ini masih terkunci. Selesaikan level sebelumnya dulu.');
        }

        $level    = $found['level'];
        $progress = new ProgressService();
        $row      = $progress->getProgress($this->userId(), $levelId); // level baru = hearts penuh

        return $this->render('level/show', [
            'level'    => $level,
            'chapter'  => $found['chapter'],
            'cards'    => model(MaterialCardModel::class)->forLevel($levelId),
            'hearts'   => $progress->heartsStatus($row),
            'isReplay' => $progress->isReplay($row),
            'active'   => model(LevelAttemptModel::class)->active($this->userId(), $levelId),
            'cost'     => config('Game')->heartCost[$level['type']] ?? 1,
        ]);
    }

    // Halaman bermain: kuis atau sandbox boss
    public function play(int $levelId)
    {
        $found = $this->unlockedLevel($levelId);

        if ($found === null) {
            return redirect()->to('peta')->with('error', 'Level ini masih terkunci.');
        }

        $level   = $found['level'];
        $chapter = $found['chapter'];
        $isQuiz  = $level['type'] === 'quiz';
        $result  = $isQuiz
            ? (new QuizService())->start($this->userId(), $level)
            : (new BossService())->start($this->userId(), $level);

        if (isset($result['blocked']) || isset($result['empty'])) {
            return redirect()->to('level/' . $levelId); // halaman level menampilkan alasannya
        }

        $data = ['level' => $level, 'chapter' => $chapter];

        if ($isQuiz) {
            return $this->render('level/quiz', $data + [
                'boot' => [
                    'stateUrl'  => site_url('api/kuis/' . $levelId),
                    'answerUrl' => site_url('api/kuis/' . $levelId . '/jawab'),
                    'levelUrl'  => site_url('level/' . $levelId),
                    'mapUrl'    => site_url('peta'),
                ],
            ]);
        }

        $page = (new BossService())->pageData($result['attempt'], $level);

        return $this->render('level/boss', $data + [
            'page' => $page,
            'boot' => $page + [
                'runner'    => $chapter['runner'],
                'lang'      => $chapter['slug'],
                'levelType' => $level['type'],
                'draftUrl'  => site_url('api/boss/' . $levelId . '/draf'),
                'submitUrl' => site_url('api/boss/' . $levelId . '/submit'),
                'hintUrl'   => site_url('api/boss/' . $levelId . '/petunjuk'),
                'levelUrl'  => site_url('level/' . $levelId),
                'playUrl'   => site_url('level/' . $levelId . '/main'),
                'mapUrl'    => site_url('peta'),
                'vendorUrl' => base_url('vendor'),
                'assetsUrl' => base_url('assets'),
            ],
        ]);
    }
}
