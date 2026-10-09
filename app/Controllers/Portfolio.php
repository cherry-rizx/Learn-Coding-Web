<?php

namespace App\Controllers;

use App\Models\SubmissionModel;

// Portofolio: submit lulus terakhir per level boss
class Portfolio extends BaseController
{
    public function index()
    {
        return $this->render('portfolio/index', [
            'items' => model(SubmissionModel::class)->portfolio($this->userId()),
        ]);
    }
}
