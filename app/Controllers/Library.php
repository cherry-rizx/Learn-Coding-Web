<?php

namespace App\Controllers;

use App\Models\MaterialCardModel;
use App\Services\CurriculumService;

// Perpustakaan materi: hanya kartu dari level yang sudah terbuka
class Library extends BaseController
{
    public function index()
    {
        $map   = (new CurriculumService())->map($this->userId(), $this->user()?->ingroup('admin') ?? false);
        $ids   = [];
        $index = [];

        foreach ($map as $chapter) {
            foreach ($chapter['levels'] as $level) {
                if ($level['unlocked']) {
                    $ids[]              = (int) $level['id'];
                    $index[$level['id']] = ['level' => $level, 'chapter' => $chapter];
                }
            }
        }

        $cards = $ids === [] ? [] : model(MaterialCardModel::class)
            ->whereIn('level_id', $ids)
            ->orderBy('level_id')
            ->orderBy('order_no')
            ->findAll();

        // Kelompokkan: bab -> level -> kartu
        $groups = [];

        foreach ($cards as $card) {
            $info = $index[$card['level_id']];
            $slug = $info['chapter']['slug'];

            $groups[$slug]['chapter']                               ??= $info['chapter'];
            $groups[$slug]['levels'][$card['level_id']]['level']    ??= $info['level'];
            $groups[$slug]['levels'][$card['level_id']]['cards'][]   = $card;
        }

        return $this->render('library/index', [
            'groups' => $groups,
            'focus'  => (int) ($this->request->getGet('level') ?? 0),
        ]);
    }
}
