<?php

namespace App\Models;

use CodeIgniter\Model;

class MaterialCardModel extends Model
{
    protected $table         = 'material_cards';
    protected $returnType    = 'array';
    protected $allowedFields = ['level_id', 'order_no', 'title', 'body', 'code_example'];
    protected $useTimestamps = true;

    public function forLevel(int $levelId): array
    {
        return $this->where('level_id', $levelId)->orderBy('order_no')->findAll();
    }
}
