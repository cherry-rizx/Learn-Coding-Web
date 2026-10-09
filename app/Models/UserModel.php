<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;

class UserModel extends ShieldUserModel
{
    protected function initialize(): void
    {
        parent::initialize();

        // Daftarkan kolom game supaya bisa disimpan lewat model
        $this->allowedFields = [
            ...$this->allowedFields,
            'is_guest',
            'tutorial_seen',
            'total_stars',
            'stars_updated_at',
        ];
    }
}
