<?php

namespace App\Models;

use CodeIgniter\Model;

class QuestionOptionModel extends Model
{
    protected $table         = 'question_options';
    protected $returnType    = 'array';
    protected $allowedFields = ['question_id', 'content', 'is_correct', 'correct_position'];
    protected $useTimestamps = true;

    // Kolom kunci jawaban sengaja tidak dipilih
    public function forQuiz(array $questionIds): array
    {
        $options = $this->select('id, question_id, content')->whereIn('question_id', $questionIds)->findAll();
        shuffle($options);

        return $options;
    }
}
