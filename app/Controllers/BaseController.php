<?php

namespace App\Controllers;

use App\Services\CurriculumService;
use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Entities\User;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);
    }

    protected function user(): ?User
    {
        return auth()->user();
    }

    protected function userId(): int
    {
        return (int) auth()->id();
    }

    // Level + bab + status. 404 kalau tidak ada, null kalau masih terkunci
    protected function unlockedLevel(int $levelId): ?array
    {
        $found = (new CurriculumService())->findLevel($this->userId(), $levelId);

        if ($found === null) {
            throw PageNotFoundException::forPageNotFound('Level tidak ditemukan.');
        }

        return $found['level']['unlocked'] ? $found : null;
    }

    // Data umum layout: user, total bintang
    protected function render(string $view, array $data = []): string
    {
        return view($view, $data + ['currentUser' => $this->user()]);
    }
}
