<?php

namespace App\Controllers;

use CodeIgniter\Shield\Entities\User;

class Account extends BaseController
{
    // Mode tamu: buat baris user tanpa email, lalu langsung login
    public function guest()
    {
        if (auth()->loggedIn()) {
            return redirect()->to('peta');
        }

        $users = auth()->getProvider();
        $users->save(new User(['username' => null, 'active' => 1, 'is_guest' => 1]));

        $user = $users->findById($users->getInsertID());
        $users->addToDefaultGroup($user);
        auth()->login($user);

        return redirect()->to('peta')->with('message', 'Kamu masuk sebagai tamu. Daftar kapan saja supaya progres tersimpan permanen.');
    }

    // Tamu yang ingin masuk ke akun lain: keluar dulu (progres tamu ditinggal)
    public function leaveGuest()
    {
        $user = $this->user();

        if ($user !== null && (int) $user->is_guest === 1) {
            auth()->logout();
        }

        return redirect()->to('login');
    }

    public function tutorialDone()
    {
        auth()->getProvider()->update($this->userId(), ['tutorial_seen' => 1]);

        return $this->response->setJSON(['ok' => true]);
    }
}
