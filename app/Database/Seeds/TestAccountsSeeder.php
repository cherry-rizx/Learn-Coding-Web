<?php

namespace App\Database\Seeds;

use App\Models\UserModel;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;
use RuntimeException;

/**
 * Local accounts for manually checking user and admin screens.
 * Run with: php spark db:seed TestAccountsSeeder
 */
class TestAccountsSeeder extends Seeder
{
    private const ACCOUNTS = [
        [
            'username' => 'qa-user',
            'email'    => 'user.test@misikoding.test',
            'password' => 'UserTest!2026',
            'group'    => 'user',
        ],
        [
            'username' => 'qa-admin',
            'email'    => 'admin.test@misikoding.test',
            'password' => 'AdminTest!2026',
            'group'    => 'admin',
        ],
    ];

    public function run()
    {
        if (ENVIRONMENT === 'production') {
            throw new RuntimeException('TestAccountsSeeder hanya boleh dijalankan di development atau testing.');
        }

        foreach (self::ACCOUNTS as $account) {
            $user   = $this->findOrCreate($account);
            $groups = $user->getGroups();

            if (! in_array($account['group'], $groups, true)) {
                $user->addGroup($account['group']);
            }

            CLI::write(sprintf('%s account siap: %s', ucfirst($account['group']), $account['email']), 'green');
        }

        CLI::write('Akun yang sudah ada tidak diubah password-nya.', 'yellow');
    }

    /** @param array{username: string, email: string, password: string, group: string} $account */
    private function findOrCreate(array $account): User
    {
        $identity = $this->db->table('auth_identities')
            ->select('user_id')
            ->where('type', 'email_password')
            ->where('secret', $account['email'])
            ->get()
            ->getRowArray();

        $users = model(UserModel::class);

        if ($identity !== null) {
            $user = $users->findById((int) $identity['user_id']);

            if ($user === null) {
                throw new RuntimeException('Identitas email ditemukan tanpa user: ' . $account['email']);
            }

            return $user;
        }

        $users->save(new User([
            'username'      => $account['username'],
            'active'        => 1,
            'is_guest'      => 0,
            'tutorial_seen' => 1,
        ]));

        $user = $users->findById($users->getInsertID());

        if ($user === null) {
            throw new RuntimeException('Gagal membuat user uji: ' . $account['email']);
        }

        $user->createEmailIdentity([
            'email'    => $account['email'],
            'password' => $account['password'],
        ]);
        $users->addToDefaultGroup($user);

        return $user;
    }
}
