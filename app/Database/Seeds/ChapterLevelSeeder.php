<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

class ChapterLevelSeeder extends Seeder
{
    public function run()
    {
        // Aman dijalankan berulang: lewati kalau bab sudah ada
        if ($this->db->table('chapters')->countAllResults() > 0) {
            CLI::write('Bab sudah ada, seeder dilewati.', 'yellow');

            return;
        }

        $chapters = [
            ['order_no' => 1, 'name' => 'HTML', 'slug' => 'html', 'runner' => 'iframe'],
            ['order_no' => 2, 'name' => 'CSS', 'slug' => 'css', 'runner' => 'iframe'],
            ['order_no' => 3, 'name' => 'JavaScript', 'slug' => 'javascript', 'runner' => 'iframe'],
            ['order_no' => 4, 'name' => 'SQL', 'slug' => 'sql', 'runner' => 'sqljs'],
            ['order_no' => 5, 'name' => 'PHP', 'slug' => 'php', 'runner' => 'phpwasm'],
        ];
        $now = date('Y-m-d H:i:s');

        foreach ($chapters as $chapter) {
            $this->db->table('chapters')->insert($chapter + ['created_at' => $now, 'updated_at' => $now]);
            $chapterId = $this->db->insertID();

            $levels = [];

            for ($no = 1; $no <= 10; $no++) {
                $levels[] = [
                    'chapter_id' => $chapterId,
                    'level_no'   => $no,
                    'type'       => match ($no) {
                        5       => 'mini_boss',
                        10      => 'final_boss',
                        default => 'quiz',
                    },
                    'title'      => "{$chapter['name']} - Level {$no}", // judul sementara
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $this->db->table('levels')->insertBatch($levels);
        }

        CLI::write('5 bab dan 50 level berhasil dibuat.', 'green');
    }
}
