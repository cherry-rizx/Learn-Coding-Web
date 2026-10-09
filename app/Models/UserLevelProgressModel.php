<?php

namespace App\Models;

use CodeIgniter\Model;

class UserLevelProgressModel extends Model
{
    protected $table         = 'user_level_progress';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'level_id', 'best_stars', 'hearts_left', 'hearts_updated_at', 'completed_at'];
    protected $useTimestamps = true;
}
