<?php

namespace App\Models;

use CodeIgniter\Model;

class AttemptAnswerModel extends Model
{
    protected $table         = 'attempt_answers';
    protected $returnType    = 'array';
    protected $allowedFields = ['attempt_id', 'question_id', 'is_correct'];
    protected $useTimestamps = true;
    protected $createdField  = 'answered_at';
    protected $updatedField  = '';
}
