<?php

namespace App\Controllers;

use App\Services\CurriculumService;
use App\Services\RankService;

class Map extends BaseController
{
    public function index()
    {
        $curriculum = new CurriculumService();
        $user       = $this->user();
        $isAdmin    = $user?->ingroup('admin') ?? false;
        $map        = $curriculum->map($this->userId(), $isAdmin);

        return $this->render('map/index', [
            'map'          => $map,
            'summary'      => $curriculum->summary($map),
            'rank'         => (int) $user->is_guest === 1 ? null : (new RankService())->rankOf($this->userId()),
            'showTutorial' => (int) $user->tutorial_seen === 0,
            'isAdmin'      => $isAdmin,
            'config'       => config('Game'),
        ]);
    }
}
