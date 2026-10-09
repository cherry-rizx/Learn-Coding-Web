<?php

namespace App\Models;

use CodeIgniter\Model;

class QuestionModel extends Model
{
    protected $table         = 'questions';
    protected $returnType    = 'array';
    protected $allowedFields = ['level_id', 'type', 'prompt', 'clue', 'explanation'];
    protected $useTimestamps = true;

    // Untuk browser: clue & penjelasan baru dikirim setelah user menjawab
    public function forQuiz(array $ids): array
    {
        return $this->select('id, type, prompt')->whereIn('id', $ids)->findAll();
    }
}
