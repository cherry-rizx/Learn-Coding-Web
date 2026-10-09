<?php

namespace App\Commands;

use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Game;

class GuestCleanup extends BaseCommand
{
    protected $group       = 'Game';
    protected $name        = 'guest:cleanup';
    protected $description = 'Hapus akun tamu yang tidak aktif melewati batas hari.';

    public function run(array $params)
    {
        $days   = config(Game::class)->guestRetentionDays;
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $users  = model(UserModel::class);

        // last_active diisi otomatis oleh Shield
        $ids = $users->withDeleted()
            ->where('is_guest', 1)
            ->groupStart()
                ->where('last_active <', $cutoff)
                ->orGroupStart()
                    ->where('last_active', null)
                    ->where('created_at <', $cutoff)
                ->groupEnd()
            ->groupEnd()
            ->findColumn('id') ?? [];

        if ($ids !== []) {
            $users->delete($ids, true); // true = hapus permanen, data turunan ikut terhapus (CASCADE)
        }

        CLI::write(count($ids) . ' akun tamu dihapus.', 'green');
    }
}
