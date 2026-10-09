<?php

namespace App\Models;

use CodeIgniter\Model;

class UserSocialAccountModel extends Model
{
    protected $table         = 'user_social_accounts';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'provider', 'provider_uid'];
    protected $useTimestamps = true;
    protected $updatedField  = '';
}
