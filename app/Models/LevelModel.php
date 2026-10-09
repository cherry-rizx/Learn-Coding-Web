<?php

namespace App\Models;

use CodeIgniter\Model;

class LevelModel extends Model
{
    protected $table         = 'levels';
    protected $returnType    = 'array';
    protected $allowedFields = ['chapter_id', 'level_no', 'type', 'title'];
    protected $useTimestamps = true;

    // Nomor level 1-50 dihitung, tidak disimpan
    public static function globalNumber(int $chapterOrder, int $levelNo): int
    {
        return ($chapterOrder - 1) * 10 + $levelNo;
    }
}
