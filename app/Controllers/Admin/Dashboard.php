<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ChallengeModel;
use App\Models\LevelModel;
use App\Models\MaterialCardModel;
use App\Models\QuestionModel;
use App\Models\QuestionOptionModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Dashboard extends BaseController
{
    public function index()
    {
        $db = db_connect();

        $levels = $db->table('levels l')
            ->select('l.id, l.level_no, l.type, l.title, c.name AS chapter, c.order_no')
            ->join('chapters c', 'c.id = l.chapter_id')
            ->orderBy('c.order_no')
            ->orderBy('l.level_no')
            ->get()->getResultArray();

        $questions  = array_column($db->table('questions')->select('level_id, COUNT(*) AS total')->groupBy('level_id')->get()->getResultArray(), 'total', 'level_id');
        $challenges = array_column($db->table('challenges')->select('level_id, COUNT(*) AS total')->groupBy('level_id')->get()->getResultArray(), 'total', 'level_id');

        foreach ($levels as &$level) {
            $level['questions']  = (int) ($questions[$level['id']] ?? 0);
            $level['challenges'] = (int) ($challenges[$level['id']] ?? 0);
        }
        unset($level);

        return $this->render('admin/dashboard', [
            'levels'         => $levels,
            'totalQuestions' => array_sum($questions),
        ]);
    }

    public function level(int $levelId)
    {
        $level = model(LevelModel::class)->find($levelId)
            ?? throw PageNotFoundException::forPageNotFound('Level tidak ditemukan.');

        $questions = model(QuestionModel::class)->where('level_id', $levelId)->orderBy('id')->findAll();

        foreach ($questions as &$question) {
            $question['options'] = model(QuestionOptionModel::class)
                ->where('question_id', $question['id'])
                ->orderBy('id')
                ->findAll();
        }
        unset($question);

        return $this->render('admin/level', [
            'level'      => $level,
            'chapter'    => db_connect()->table('chapters')->where('id', $level['chapter_id'])->get()->getRowArray(),
            'cards'      => model(MaterialCardModel::class)->forLevel($levelId),
            'questions'  => $questions,
            'challenges' => model(ChallengeModel::class)->where('level_id', $levelId)->orderBy('variant_no')->findAll(),
        ]);
    }
}
