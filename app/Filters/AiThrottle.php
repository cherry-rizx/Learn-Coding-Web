<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Game;

// Batasi panggilan AI per user supaya 9Router tidak dibanjiri
class AiThrottle implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $limit = config(Game::class)->aiHintsPerMinute;
        $key   = 'ai-hint-' . (auth()->id() ?? $request->getIPAddress());

        if (service('throttler')->check(md5($key), $limit, MINUTE) === false) {
            return service('response')->setStatusCode(429)->setJSON([
                'error'   => 'throttled',
                'message' => 'Terlalu sering meminta petunjuk. Tunggu sebentar.',
            ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
