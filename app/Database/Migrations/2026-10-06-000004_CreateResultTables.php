<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateResultTables extends Migration
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
            'id'         => $this->id(),
            'attempt_id' => $this->ref(),
            'code'       => ['type' => 'TEXT'],
            'passed'     => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['attempt_id', 'passed']);
        $this->forge->addForeignKey('attempt_id', 'level_attempts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('submissions');

        $this->forge->addField([
            'id'            => $this->id(),
            'attempt_id'    => $this->ref(),
            'submission_id' => $this->ref(true),
            'trigger_type'  => ['type' => 'ENUM', 'constraint' => ['manual', 'auto']], // "trigger" kata kunci MySQL
            'response'      => ['type' => 'TEXT', 'null' => true],
            'status'        => ['type' => 'ENUM', 'constraint' => ['ok', 'fallback']],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('attempt_id', 'level_attempts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('submission_id', 'submissions', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('ai_hints');

        $this->forge->addField([
            'id'        => $this->id(),
            'user_id'   => $this->ref(),
            'code'      => ['type' => 'VARCHAR', 'constraint' => 32],
            'issued_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('user_id');
        $this->forge->addUniqueKey('code');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('certificates');
    }

    public function down()
    {
        $this->forge->dropTable('certificates', true);
        $this->forge->dropTable('ai_hints', true);
        $this->forge->dropTable('submissions', true);
    }
}
