<?php

namespace App\Models;

use CodeIgniter\Model;

class CertificateModel extends Model
{
    protected $table         = 'certificates';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'code'];
    protected $useTimestamps = true;
    protected $createdField  = 'issued_at';
    protected $updatedField  = '';

    // Kode acak 16 karakter untuk halaman verifikasi
    public static function generateCode(): string
    {
        return strtoupper(bin2hex(random_bytes(8)));
    }
}
