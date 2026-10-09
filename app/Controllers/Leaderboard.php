<?php

namespace App\Controllers;

use App\Services\RankService;

class Leaderboard extends BaseController
{
    public function index()
    {
        $rank = new RankService();
        $user = $this->user();

        return $this->render('leaderboard/index', [
            'rows'    => $rank->top(),
            'myRank'  => $rank->rankOf($this->userId()),
            'isGuest' => (int) $user->is_guest === 1,
            'myStars' => (int) $user->total_stars,
        ]);
    }
}
