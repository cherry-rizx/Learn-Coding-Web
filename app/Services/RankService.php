<?php

namespace App\Services;

use Config\Game;

// Leaderboard sepanjang masa: total bintang, seri -> yang lebih dulu mencapainya
class RankService
{
    public function top(?int $limit = null): array
    {
        $limit ??= config(Game::class)->leaderboardSize;

        return db_connect()->table('users')
            ->select('id, username, total_stars, stars_updated_at')
            ->where('is_guest', 0)
            ->where('deleted_at', null)
            ->where('total_stars >', 0)
            ->orderBy('total_stars', 'DESC')
            ->orderBy('stars_updated_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->limit($limit)
            ->get()->getResultArray();
    }

    // Peringkat satu user, null kalau tamu atau belum punya bintang
    public function rankOf(int $userId): ?int
    {
        $db  = db_connect();
        $me  = $db->table('users')->select('total_stars, stars_updated_at, is_guest')->where('id', $userId)->get()->getRowArray();

        if ($me === null || (int) $me['is_guest'] === 1 || (int) $me['total_stars'] === 0) {
            return null;
        }

        $query = $db->table('users')
            ->where('is_guest', 0)
            ->where('deleted_at', null)
            ->groupStart()
                ->where('total_stars >', $me['total_stars']);

        // Seri: yang lebih dulu mencapai angka itu berada di atas
        if ($me['stars_updated_at'] !== null) {
            $query->orGroupStart()
                ->where('total_stars', $me['total_stars'])
                ->where('stars_updated_at <', $me['stars_updated_at'])
            ->groupEnd();
        }

        $ahead = $query->groupEnd()->countAllResults();

        return $ahead + 1;
    }
}
