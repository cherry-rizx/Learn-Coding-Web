<?php

namespace App\Models;

use CodeIgniter\Model;

class LevelAttemptModel extends Model
{
    protected $table         = 'level_attempts';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'user_id', 'level_id', 'challenge_id', 'status', 'is_replay', 'question_order',
        'current_index', 'correct_count', 'fail_count', 'draft_code', 'stars_earned', 'finished_at',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'started_at';

    // Sesi yang masih berjalan, dipakai saat user refresh
    public function active(int $userId, int $levelId): ?array
    {
        return $this->where(['user_id' => $userId, 'level_id' => $levelId, 'status' => 'active'])->first();
    }

    // Siklus boss terakhir, untuk menghindari variasi yang sama
    public function lastFinished(int $userId, int $levelId): ?array
    {
        return $this->where(['user_id' => $userId, 'level_id' => $levelId, 'status !=' => 'active'])
            ->orderBy('id', 'DESC')
            ->first();
    }
}
