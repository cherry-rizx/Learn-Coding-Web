<?php

namespace App\Database\Seeds;

use App\Models\CertificateModel;
use App\Models\UserModel;
use App\Services\ProgressService;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;

/**
 * Data untuk presentasi: php spark db:seed DemoSeeder
 * - demo@misikoding.test  / demo12345   : tamat 50 level, 150 bintang, sertifikat
 * - siswa@misikoding.test / siswa12345  : baru menyelesaikan level 1-4
 * - beberapa pemain lain supaya leaderboard terisi
 */
class DemoSeeder extends Seeder
{
    public function run()
    {
        if ($this->db->table('auth_identities')->where('secret', 'demo@misikoding.test')->countAllResults() > 0) {
            CLI::write('Akun demo sudah ada, seeder dilewati.', 'yellow');

            return;
        }

        $levels = $this->db->table('levels')
            ->select('levels.id, levels.type')
            ->join('chapters', 'chapters.id = levels.chapter_id')
            ->orderBy('chapters.order_no')
            ->orderBy('levels.level_no')
            ->get()->getResultArray();

        // Akun tamat: semua level 3 bintang
        $demo = $this->createUser('demo', 'demo@misikoding.test', 'demo12345');
        $this->giveStars($demo, $levels, array_fill(0, count($levels), 3), '-20 days');
        $this->addPortfolio($demo, $levels);
        model(CertificateModel::class)->insert(['user_id' => $demo, 'code' => CertificateModel::generateCode()]);
        $this->setStarsTime($demo, '-2 days');

        // Akun siswa: level 1-4 selesai, berikutnya mini boss
        $siswa = $this->createUser('siswa', 'siswa@misikoding.test', 'siswa12345');
        $this->giveStars($siswa, $levels, [3, 2, 3, 3], '-3 days');
        $this->setStarsTime($siswa, '-1 days');

        // Pemain lain untuk leaderboard (pola bintang per level, diulang)
        $players = [
            ['rina', [3, 3, 3, 2, 3, 3, 3, 3, 2, 3], 27, '-5 days'],
            ['budi', [3, 2, 3, 3, 2, 3, 2, 3, 3, 2], 21, '-4 days'],
            ['sari', [2, 3, 2, 2, 3], 17, '-6 days'],
            ['agus', [3, 3, 2], 12, '-3 days'],
            ['dewi', [2, 2], 8, '-2 days'],
            ['tono', [3], 4, '-1 days'],
        ];

        foreach ($players as [$name, $pattern, $count, $time]) {
            $id    = $this->createUser($name, $name . '@misikoding.test', $name . '12345');
            $stars = [];

            for ($i = 0; $i < $count; $i++) {
                $stars[] = $pattern[$i % count($pattern)];
            }

            $this->giveStars($id, $levels, $stars, '-30 days');
            $this->setStarsTime($id, $time);
        }

        CLI::write('Akun demo dibuat: demo@misikoding.test / demo12345, siswa@misikoding.test / siswa12345', 'green');
    }

    private function createUser(string $username, string $email, string $password): int
    {
        $users = model(UserModel::class);
        $users->save(new User(['username' => $username, 'active' => 1, 'is_guest' => 0, 'tutorial_seen' => 1]));

        $user = $users->findById($users->getInsertID());
        $user->createEmailIdentity(['email' => $email, 'password' => $password]);
        $users->addToDefaultGroup($user);

        return (int) $user->id;
    }

    // Isi best_stars level berurutan sesuai daftar $stars
    private function giveStars(int $userId, array $levels, array $stars, string $since): void
    {
        $time = strtotime($since);

        foreach ($stars as $i => $star) {
            $done = date('Y-m-d H:i:s', $time + $i * 3600);

            $this->db->table('user_level_progress')->insert([
                'user_id'      => $userId,
                'level_id'     => $levels[$i]['id'],
                'best_stars'   => $star,
                'hearts_left'  => 5,
                'completed_at' => $star >= 2 ? $done : null,
                'created_at'   => $done,
                'updated_at'   => $done,
            ]);
        }

        (new ProgressService())->refreshTotalStars($userId);
    }

    // Waktu pencapaian total bintang, penentu urutan saat seri
    private function setStarsTime(int $userId, string $when): void
    {
        $this->db->table('users')->where('id', $userId)->update(['stars_updated_at' => date('Y-m-d H:i:s', strtotime($when))]);
    }

    // Portofolio: submit lulus memakai solusi referensi di level boss yang punya konten
    private function addPortfolio(int $userId, array $levels): void
    {
        foreach ($levels as $level) {
            if ($level['type'] === 'quiz') {
                continue;
            }

            $challenge = $this->db->table('challenges')->where('level_id', $level['id'])->orderBy('variant_no')->get()->getRowArray();

            if ($challenge === null) {
                continue;
            }

            $now = date('Y-m-d H:i:s', strtotime('-10 days'));
            $this->db->table('level_attempts')->insert([
                'user_id'      => $userId,
                'level_id'     => $level['id'],
                'challenge_id' => $challenge['id'],
                'status'       => 'passed',
                'stars_earned' => 3,
                'draft_code'   => $challenge['solution_code'],
                'started_at'   => $now,
                'updated_at'   => $now,
                'finished_at'  => $now,
            ]);
            $this->db->table('submissions')->insert([
                'attempt_id' => $this->db->insertID(),
                'code'       => $challenge['solution_code'],
                'passed'     => 1,
                'created_at' => $now,
            ]);
        }
    }
}
