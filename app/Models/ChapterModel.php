<?php

namespace App\Models;

use CodeIgniter\Model;

class ChapterModel extends Model
{
    protected $table         = 'chapters';
    protected $returnType    = 'array';
    protected $allowedFields = ['order_no', 'name', 'slug', 'runner'];
    protected $useTimestamps = true;
}
