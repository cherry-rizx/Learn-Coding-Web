<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * Mengisi konten level dari app/Database/Content/*.json.
 * Aman dijalankan ulang: konten level yang ada di JSON diganti total.
 *
 * Field teks panjang (body, code, starter, setup, solution, instruction)
 * boleh berupa string atau array baris (digabung dengan "\n").
 */
class ContentSeeder extends Seeder
{
    public function run()
    {
        $files = glob(APPPATH . 'Database/Content/*.json') ?: [];
        sort($files);

        foreach ($files as $file) {
            $data = json_decode((string) file_get_contents($file), true);

            if (! is_array($data)) {
                throw new RuntimeException('JSON tidak valid: ' . basename($file) . ' (' . json_last_error_msg() . ')');
            }

            $this->seedChapter($data, basename($file));
        }
    }

    private function seedChapter(array $data, string $file): void
    {
        $chapter = $this->db->table('chapters')->where('slug', $data['chapter'])->get()->getRowArray();

        if ($chapter === null) {
            throw new RuntimeException("Bab '{$data['chapter']}' belum ada. Jalankan ChapterLevelSeeder dulu.");
        }

        $now   = date('Y-m-d H:i:s');
        $count = ['cards' => 0, 'questions' => 0, 'challenges' => 0];

        foreach ($data['levels'] as $item) {
            $level = $this->db->table('levels')
                ->where(['chapter_id' => $chapter['id'], 'level_no' => $item['level_no']])
                ->get()->getRowArray();

            if ($level === null) {
                throw new RuntimeException("{$file}: level_no {$item['level_no']} tidak ditemukan.");
            }

            $levelId = (int) $level['id'];
            $this->db->table('levels')->where('id', $levelId)->update(['title' => $item['title'], 'updated_at' => $now]);

            // Konten lama diganti; sesi aktif ditutup supaya tidak menunjuk soal lama
            $this->db->table('level_attempts')->where(['level_id' => $levelId, 'status' => 'active'])->delete();
            $this->db->table('material_cards')->where('level_id', $levelId)->delete();
            $this->db->table('questions')->where('level_id', $levelId)->delete();
            $this->db->table('challenges')->where('level_id', $levelId)->delete();

            foreach ($item['cards'] ?? [] as $i => $card) {
                $this->db->table('material_cards')->insert([
                    'level_id'     => $levelId,
                    'order_no'     => $i + 1,
                    'title'        => $card['title'],
                    'body'         => $this->text($card['body']),
                    'code_example' => isset($card['code']) ? $this->text($card['code']) : null,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
                $count['cards']++;
            }

            foreach ($item['questions'] ?? [] as $i => $q) {
                $this->insertQuestion($levelId, $q, "{$file} level {$item['level_no']} soal " . ($i + 1), $now);
                $count['questions']++;
            }

            foreach ($item['challenges'] ?? [] as $i => $c) {
                if (empty($c['tests'])) {
                    throw new RuntimeException("{$file} level {$item['level_no']}: tantangan tanpa test case.");
                }

                $this->db->table('challenges')->insert([
                    'level_id'      => $levelId,
                    'variant_no'    => $i + 1,
                    'instruction'   => $this->text($c['instruction']),
                    'starter_code'  => $this->text($c['starter'] ?? ''),
                    'setup_code'    => isset($c['setup']) ? $this->text($c['setup']) : null,
                    'test_cases'    => json_encode($c['tests'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'solution_code' => $this->text($c['solution']),
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
                $count['challenges']++;
            }

            // Level yang hanya berisi judul (konten menyusul) tidak diberi peringatan
            if ($level['type'] === 'quiz' && isset($item['questions']) && count($item['questions']) < 10) {
                CLI::write("  ! {$file} level {$item['level_no']}: soal kurang dari 10, level belum bisa dimainkan.", 'yellow');
            }
        }

        CLI::write(sprintf(
            '%s: %d kartu, %d soal, %d tantangan.',
            $data['chapter'],
            $count['cards'],
            $count['questions'],
            $count['challenges'],
        ), 'green');
    }

    private function insertQuestion(int $levelId, array $q, string $where, string $now): void
    {
        $this->db->table('questions')->insert([
            'level_id'    => $levelId,
            'type'        => $q['type'],
            'prompt'      => $this->text($q['prompt']),
            'clue'        => $q['clue'],
            'explanation' => $q['explanation'],
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
        $questionId = (int) $this->db->insertID();
        $options    = [];

        if ($q['type'] === 'mcq') {
            if (! isset($q['options'][$q['answer']])) {
                throw new RuntimeException("{$where}: indeks jawaban tidak valid.");
            }

            foreach ($q['options'] as $i => $text) {
                $options[] = ['content' => $text, 'is_correct' => $i === $q['answer'] ? 1 : 0, 'correct_position' => null];
            }
        } else {
            if (empty($q['answer'])) {
                throw new RuntimeException("{$where}: susunan jawaban kosong.");
            }

            foreach ($q['answer'] as $i => $text) {
                $options[] = ['content' => $text, 'is_correct' => 0, 'correct_position' => $i + 1];
            }

            foreach ($q['distractors'] ?? [] as $text) {
                $options[] = ['content' => $text, 'is_correct' => 0, 'correct_position' => null]; // pengecoh
            }
        }

        foreach ($options as $option) {
            $this->db->table('question_options')->insert($option + [
                'question_id' => $questionId,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }

    private function text(string|array $value): string
    {
        return is_array($value) ? implode("\n", $value) : $value;
    }
}
