<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContentTables extends Migration
{
    private function id(): array
    {
        return ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true];
    }

    private function ref(): array
    {
        return ['type' => 'INT', 'constraint' => 11, 'unsigned' => true];
    }

    private function timestamps(): array
    {
        return [
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ];
    }

    public function up()
    {
        $this->forge->addField([
            'id'       => $this->id(),
            'order_no' => ['type' => 'TINYINT', 'unsigned' => true],
            'name'     => ['type' => 'VARCHAR', 'constraint' => 50],
            'slug'     => ['type' => 'VARCHAR', 'constraint' => 50],
            'runner'   => ['type' => 'ENUM', 'constraint' => ['iframe', 'sqljs', 'phpwasm']],
            ...$this->timestamps(),
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('order_no');
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('chapters');

        $this->forge->addField([
            'id'         => $this->id(),
            'chapter_id' => $this->ref(),
            'level_no'   => ['type' => 'TINYINT', 'unsigned' => true],
            'type'       => ['type' => 'ENUM', 'constraint' => ['quiz', 'mini_boss', 'final_boss']],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 100],
            ...$this->timestamps(),
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['chapter_id', 'level_no']);
        $this->forge->addForeignKey('chapter_id', 'chapters', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('levels');

        $this->forge->addField([
            'id'           => $this->id(),
            'level_id'     => $this->ref(),
            'order_no'     => ['type' => 'TINYINT', 'unsigned' => true],
            'title'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'body'         => ['type' => 'TEXT'],
            'code_example' => ['type' => 'TEXT', 'null' => true],
            ...$this->timestamps(),
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['level_id', 'order_no']);
        $this->forge->addForeignKey('level_id', 'levels', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('material_cards');

        $this->forge->addField([
            'id'          => $this->id(),
            'level_id'    => $this->ref(),
            'type'        => ['type' => 'ENUM', 'constraint' => ['mcq', 'arrange']],
            'prompt'      => ['type' => 'TEXT'],
            'clue'        => ['type' => 'TEXT'],
            'explanation' => ['type' => 'TEXT'],
            ...$this->timestamps(),
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('level_id', 'levels', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('questions');

        $this->forge->addField([
            'id'               => $this->id(),
            'question_id'      => $this->ref(),
            'content'          => ['type' => 'TEXT'],
            'is_correct'       => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0], // rahasia
            'correct_position' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true], // NULL = pengecoh
            ...$this->timestamps(),
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('question_id', 'questions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('question_options');

        $this->forge->addField([
            'id'            => $this->id(),
            'level_id'      => $this->ref(),
            'variant_no'    => ['type' => 'TINYINT', 'unsigned' => true],
            'instruction'   => ['type' => 'TEXT'],
            'starter_code'  => ['type' => 'TEXT', 'null' => true],
            'setup_code'    => ['type' => 'TEXT', 'null' => true],
            'test_cases'    => ['type' => 'TEXT'], // isi JSON
            'solution_code' => ['type' => 'TEXT'], // rahasia, hanya untuk AI
            ...$this->timestamps(),
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['level_id', 'variant_no']);
        $this->forge->addForeignKey('level_id', 'levels', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('challenges');
    }

    public function down()
    {
        $this->forge->dropTable('challenges', true);
        $this->forge->dropTable('question_options', true);
        $this->forge->dropTable('questions', true);
        $this->forge->dropTable('material_cards', true);
        $this->forge->dropTable('levels', true);
        $this->forge->dropTable('chapters', true);
    }
}
