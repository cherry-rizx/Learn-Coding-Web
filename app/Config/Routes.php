<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Halaman publik
$routes->get('/', 'Home::index');
$routes->get('bantuan', 'Help::index');
$routes->get('verifikasi/(:segment)', 'Certificate::verify/$1');
$routes->post('tamu', 'Account::guest');

// Login, logout, dll dari Shield; register diganti versi kita (upgrade akun tamu)
service('auth')->routes($routes, ['except' => ['register', 'magic-link']]);
$routes->get('register', '\App\Controllers\Auth\RegisterController::registerView', ['as' => 'register']);
$routes->post('register', '\App\Controllers\Auth\RegisterController::registerAction');

// Wajib login (akun biasa maupun tamu)
$routes->group('', ['filter' => 'session'], static function (RouteCollection $routes) {
    $routes->post('tamu/keluar', 'Account::leaveGuest');
    $routes->post('tutorial/selesai', 'Account::tutorialDone');

    $routes->get('peta', 'Map::index');
    $routes->get('level/(:num)', 'Level::show/$1');
    $routes->get('level/(:num)/main', 'Level::play/$1');

    $routes->get('perpustakaan', 'Library::index');
    $routes->get('peringkat', 'Leaderboard::index');
    $routes->get('portofolio', 'Portfolio::index');
    $routes->get('sertifikat', 'Certificate::index');
    $routes->get('sertifikat/unduh', 'Certificate::download');

    // API untuk JS (JSON). CSRF tetap aktif lewat header X-CSRF-TOKEN
    $routes->group('api', static function (RouteCollection $routes) {
        $routes->get('kuis/(:num)', 'Api\Quiz::state/$1');
        $routes->post('kuis/(:num)/jawab', 'Api\Quiz::answer/$1');
        $routes->post('boss/(:num)/draf', 'Api\Boss::draft/$1');
        $routes->post('boss/(:num)/submit', 'Api\Boss::submit/$1');
        $routes->post('boss/(:num)/petunjuk', 'Api\Boss::hint/$1', ['filter' => 'aithrottle']);
    });
});
