<?php

namespace App\Models;

use CodeIgniter\Model;

class SubmissionModel extends Model
{
    protected $table         = 'submissions';
    protected $returnType    = 'array';
    protected $allowedFields = ['attempt_id', 'code', 'passed'];
    protected $useTimestamps = true;
    protected $updatedField  = '';

    // Portofolio: submit lulus terakhir per level boss
    public function portfolio(int $userId): array
    {
        $rows = $this->select('submissions.code, submissions.created_at, levels.id AS level_id, levels.title, levels.type, levels.level_no, chapters.name AS chapter_name, chapters.slug, chapters.runner, chapters.order_no')
            ->join('level_attempts', 'level_attempts.id = submissions.attempt_id')
            ->join('levels', 'levels.id = level_attempts.level_id')
            ->join('chapters', 'chapters.id = levels.chapter_id')
            ->where('level_attempts.user_id', $userId)
            ->where('submissions.passed', 1)
            ->orderBy('submissions.id', 'DESC')
            ->findAll();

        $latest = [];

        foreach ($rows as $row) {
            $latest[$row['level_id']] ??= $row; // baris pertama = submit terbaru
        }

        ksort($latest);

        return array_values($latest);
    }
}
