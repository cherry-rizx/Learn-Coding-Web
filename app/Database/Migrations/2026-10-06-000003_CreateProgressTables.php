<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProgressTables extends Migration
{
    private function id(): array
    {
        return ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true];
    }

    private function ref(bool $null = false): array
    {
        return ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => $null];
    }

    public function up()
    {
        $this->forge->addField([
            'id'                => $this->id(),
            'user_id'           => $this->ref(),
            'level_id'          => $this->ref(),
            'best_stars'        => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'hearts_left'       => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 5],
            'hearts_updated_at' => ['type' => 'DATETIME', 'null' => true],
            'completed_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'level_id']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('level_id', 'levels', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_level_progress');

        // Satu baris = satu sesi level biasa atau satu siklus boss
        $this->forge->addField([
            'id'             => $this->id(),
            'user_id'        => $this->ref(),
            'level_id'       => $this->ref(),
            'challenge_id'   => $this->ref(true),
            'status'         => ['type' => 'ENUM', 'constraint' => ['active', 'passed', 'failed'], 'default' => 'active'],
            'is_replay'      => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0],
            'question_order' => ['type' => 'TEXT', 'null' => true], // JSON berisi 10 ID soal
            'current_index'  => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'correct_count'  => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'fail_count'     => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'draft_code'     => ['type' => 'TEXT', 'null' => true],
            'stars_earned'   => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'started_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'finished_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['user_id', 'level_id', 'status']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('level_id', 'levels', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('challenge_id', 'challenges', 'id', 'CASCADE', 'SET NULL'); // riwayat tetap ada
        $this->forge->createTable('level_attempts');

        $this->forge->addField([
            'id'          => $this->id(),
            'attempt_id'  => $this->ref(),
            'question_id' => $this->ref(),
            'is_correct'  => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true], // jawaban pertama saja
            'answered_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['attempt_id', 'question_id']);
        $this->forge->addForeignKey('attempt_id', 'level_attempts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('question_id', 'questions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('attempt_answers');
    }

    public function down()
    {
        $this->forge->dropTable('attempt_answers', true);
        $this->forge->dropTable('level_attempts', true);
        $this->forge->dropTable('user_level_progress', true);
    }
}
