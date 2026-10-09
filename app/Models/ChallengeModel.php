<?php

namespace App\Models;

use CodeIgniter\Model;

class ChallengeModel extends Model
{
    protected $table         = 'challenges';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'level_id', 'variant_no', 'instruction', 'starter_code', 'setup_code', 'test_cases', 'solution_code',
    ];
    protected $useTimestamps = true;

    // Untuk browser: tanpa solution_code
    public function forBrowser(int $id): ?array
    {
        return $this->select('id, level_id, variant_no, instruction, starter_code, setup_code, test_cases')->find($id);
    }

    // Acak variasi, hindari variasi siklus sebelumnya
    public function pickVariant(int $levelId, ?int $excludeId = null): ?array
    {
        $this->where('level_id', $levelId);

        if ($excludeId !== null) {
            $this->where('id !=', $excludeId);
        }

        $ids = $this->findColumn('id') ?? [];

        if ($ids === []) {
            return $excludeId !== null ? $this->find($excludeId) : null; // hanya ada 1 variasi
        }

        return $this->find($ids[array_rand($ids)]);
    }
}
