<?php

namespace App\Controllers\Auth;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Controllers\RegisterController as ShieldRegister;

/**
 * Register Shield + upgrade akun tamu di tempat (baris user yang sama),
 * sehingga semua progres tamu ikut tersimpan.
 */
class RegisterController extends ShieldRegister
{
    public function registerView()
    {
        if ($this->isGuest()) {
            return $this->view(setting('Auth.views')['register'], ['upgrade' => true]);
        }

        return parent::registerView();
    }

    public function registerAction(): RedirectResponse
    {
        if (! $this->isGuest()) {
            return parent::registerAction();
        }

        // Email sudah terdaftar: progres tamu tidak digabung, arahkan ke login
        $email  = trim((string) $this->request->getPost('email'));
        $exists = db_connect()->table('auth_identities')
            ->where('type', 'email_password')
            ->where('secret', $email)
            ->countAllResults() > 0;

        if ($exists) {
            return redirect()->back()->withInput()->with('email_taken', true);
        }

        // Diisi sebelum validasi: aturan strong_password memeriksa data user yang login
        $user           = auth()->user();
        $user->username = $this->request->getPost('username');
        $user->email    = $email;

        $rules = $this->getValidationRules();

        if (! $this->validateData($this->request->getPost(), $rules, [], config('Auth')->DBGroup)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // save() Shield otomatis membuat identitas email+password dari entity
        $user->password = $this->request->getPost('password');
        $user->is_guest = 0;
        $this->getUserProvider()->save($user);

        return redirect()->to('peta')->with('message', 'Akun berhasil dibuat. Semua progres tamu sudah pindah ke akunmu.');
    }

    private function isGuest(): bool
    {
        $user = auth()->user();

        return $user !== null && (int) $user->is_guest === 1;
    }
}
