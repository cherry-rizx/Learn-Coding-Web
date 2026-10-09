<?php

namespace App\Controllers;

class Help extends BaseController
{
    public function index()
    {
        return $this->render('help/index', ['config' => config('Game')]);
    }
}
