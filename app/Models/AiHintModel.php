<?php

namespace App\Models;

use CodeIgniter\Model;

class AiHintModel extends Model
{
    protected $table         = 'ai_hints';
    protected $returnType    = 'array';
    protected $allowedFields = ['attempt_id', 'submission_id', 'trigger_type', 'response', 'status'];
    protected $useTimestamps = true;
    protected $updatedField  = '';
}
